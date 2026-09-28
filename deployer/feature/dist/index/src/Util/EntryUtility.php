<?php

namespace MoveElevator\FeatureIndex\Utility;

use MoveElevator\FeatureIndex\Model\Entry;
use MoveElevator\FeatureIndex\Service\ConfigReader;
use MoveElevator\FeatureIndex\Service\DeploymentReader;

class EntryUtility
{
    protected DeploymentReader $deploymentReader;
    protected ConfigReader $configReader;
    protected array $config;
    // subdomain-mode instance names are lowercased, so category/issue detection below must
    // match case-insensitively there; computed once instead of per preg_match() call
    protected string $regexFlags;

    public function __construct()
    {
        $this->configReader = new ConfigReader();
        $this->config = $this->configReader->initConfig();
        $this->deploymentReader = new DeploymentReader();
        $this->regexFlags = $this->isSubdomainMode() ? 'i' : '';
    }

    /**
     * @param string $name
     * @param string $basePath
     * @return \MoveElevator\FeatureIndex\Model\Entry
     */
    public function generateEntry(string $name, string $basePath): Entry
    {
        $entry = new Entry($name);

        $entry->setDeployment($this->deploymentReader->read($basePath . '/' . $name, $basePath));
        $entry->setCategory($this->getEntryCategory($name));
        $entry->setTag($this->getEntryTag($name));
        $entry->setIssue($this->getEntryIssue($name));

        return $entry;
    }

    /**
     * @param Entry[] $entries
     * @return array{reference: Entry[], feature: Entry[], release: Entry[]}
     */
    public function groupEntries(array $entries): array
    {
        $groups = ['reference' => [], 'feature' => [], 'release' => []];
        foreach ($entries as $entry) {
            $groups[in_array($entry->getCategory(), ['feature', 'release'], true) ? $entry->getCategory() : 'reference'][] = $entry;
        }

        $order = ['main', 'master', 'stage', 'test'];
        usort($groups['reference'], fn (Entry $a, Entry $b) => [$this->searchArrayLike($a->getName(), $order), $a->getName()] <=> [$this->searchArrayLike($b->getName(), $order), $b->getName()]);
        // most recently deployed first, that is what is being worked on
        usort($groups['feature'], static fn (Entry $a, Entry $b) => $b->getDeployment()->timestamp <=> $a->getDeployment()->timestamp);
        usort($groups['release'], static fn (Entry $a, Entry $b) => version_compare(ltrim($b->getTag(), 'v'), ltrim($a->getTag(), 'v')));

        return $groups;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getEntryCategory(string $name): string
    {
        if (preg_match('/(release)-(\d+\.\d+\.\d+)/' . $this->regexFlags, $name)) return 'release';
        if (preg_match('/([A-Z]+)-(\d+)/' . $this->regexFlags, $name)) return 'feature';
        return $name;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getEntryTag(string $name): string
    {
        if (preg_match('/(release)-(\d+\.\d+\.\d+)/' . $this->regexFlags, $name, $version)) return 'v' . $version[2];
        return '';
    }

    /**
     * @param string $name
     * @return string
     */
    private function getEntryIssue(string $name): string
    {
        if (preg_match('/([A-Z]+)-(\d+)/' . $this->regexFlags, $name, $issue)) {
            // subdomain-mode instance names are lowercased, restore the Jira issue key case
            return $this->isSubdomainMode() ? strtoupper($issue[0]) : $issue[0];
        }
        return '';
    }

    /**
     * Whether instance names are hostname-safe (lowercased) rather than the original branch
     * casing, i.e. feature_url_pattern is configured (see IOService::getEntryAppPath()).
     */
    private function isSubdomainMode(): bool
    {
        return !empty($this->config['featureUrlPattern'] ?? '');
    }

    /**
     * @param string $haystack
     * @param array $array
     * @return int
     */
    private function searchArrayLike(string $haystack, array $array): int
    {
        foreach ($array as $key => $needle) {
            if (strpos($haystack, $needle) === 0) {
                return $key;
            }
        }
        return 999;
    }

}
