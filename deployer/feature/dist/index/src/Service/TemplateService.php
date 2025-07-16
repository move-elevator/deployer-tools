<?php

namespace MoveElevator\FeatureIndex\Service;

use MoveElevator\FeatureIndex\Model\Entry;
use MoveElevator\FeatureIndex\Utility\EntryUtility;

class TemplateService
{
    /**
     * @param array $entries
     * @return string
     */
    public function renderEntries(array $entries): string
    {
        $html = '';
        $ioService = new IOService();
        $configReader = new ConfigReader();
        $config = $configReader->initConfig();

        foreach ($entries as $entry) {
            $html .= "<tr>" .
                "<td class='branch'>" .
                "<a class='pill-wrapper' href='" . $config['git']['branch'] . $entry->getName() . "' target='_blank'><div class='pill' data-tooltip='" . ucfirst($entry->getCategory()) . " branch' data-type='" . $entry->getCategory() . "'>" . strtoupper($entry->getCategory()[0]) . "</div></a>" .
                "<a class='entry' href='" . $ioService->getEntryAppPath($entry) . "'><strong>" . $entry->getName() . "</strong></a> <sup>" . $entry->getTag() . "</sup>" .
                "</td>" .
                "<td style='text-align: right;'>" .
                $this->renderIssueData($entry) .
                "</td>" .
                "<td style='text-align: right;'>" .
                $this->renderLastUpdated($entry) .
                "</td>" .
                "</tr>";
        }

        return $html;
    }

    /**
     * @param string $links
     * @return string
     */
    public function listAdditionalLinks(string $links): string
    {
        if ($links == '') return '';

        $html = "<li><details role='list' dir='rtl'><summary aria-haspopup='listbox' role='link'></summary><ul role='listbox'>";
        $items = explode(',', $links);

        foreach ($items as $item) {
            $link = explode('|', $item);
            $html .= "<li><a href='" . $link[1] . "' target='_blank'>" . $link[0] . "</a></li>";
        }
        $html .= "</ul></details></li>";
        return $html;
    }

    /**
     * @param string $type
     * @return string|void
     */
    public function getApplicationType(string $type)
    {
        if (strtolower($type) === 'symfony') {
            return '<svg version="1.1" id="symfony" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 122.88 122.88" style="enable-background:new 0 0 122.88 122.88" xml:space="preserve"><g><path d="M122.88,61.44c0,33.93-27.51,61.44-61.44,61.44C27.51,122.88,0,95.37,0,61.44C0,27.51,27.51,0,61.44,0 C95.37,0,122.88,27.51,122.88,61.44L122.88,61.44z M88.3,22.73c-6.24,0.21-11.69,3.66-15.75,8.41c-4.49,5.22-7.48,11.41-9.63,17.72 c-3.85-3.16-6.82-7.24-13-9.02c-4.78-1.37-9.79-0.81-14.4,2.63c-2.18,1.63-3.69,4.09-4.4,6.42c-1.85,6.02,1.95,11.39,3.67,13.31 l3.77,4.04c0.78,0.79,2.65,2.86,1.74,5.83c-0.99,3.23-4.88,5.31-8.87,4.09c-1.78-0.55-4.34-1.87-3.77-3.74 c0.24-0.77,0.78-1.34,1.08-1.99c0.27-0.57,0.4-0.99,0.48-1.25c0.73-2.38-0.27-5.47-2.81-6.26c-2.38-0.73-4.81-0.15-5.75,2.91 c-1.07,3.48,0.6,9.79,9.51,12.53c10.44,3.22,19.28-2.47,20.53-9.89c0.79-4.64-1.31-8.1-5.15-12.53l-3.13-3.47 c-1.89-1.89-2.55-5.12-0.58-7.6c1.66-2.1,4.01-2.99,7.88-1.94c5.64,1.53,8.15,5.44,12.35,8.6c-1.73,5.68-2.86,11.39-3.89,16.5 l-0.63,3.81c-3,15.72-5.29,24.36-11.24,29.32c-1.2,0.85-2.91,2.13-5.49,2.22c-1.36,0.04-1.79-0.89-1.81-1.3 c-0.03-0.95,0.77-1.39,1.3-1.81c0.8-0.43,2-1.15,1.91-3.46c-0.08-2.72-2.34-5.08-5.6-4.97c-2.44,0.08-6.16,2.38-6.02,6.58 c0.14,4.35,4.19,7.6,10.3,7.4c3.26-0.11,10.55-1.44,17.73-9.98C67,86.07,69.34,74.85,71.1,66.64l1.96-10.84 c1.09,0.13,2.26,0.22,3.53,0.25c10.41,0.22,15.62-5.17,15.7-9.1c0.05-2.37-1.56-4.71-3.81-4.66c-1.61,0.04-3.64,1.12-4.12,3.35 c-0.48,2.18,3.31,4.16,0.35,6.08c-2.1,1.36-5.87,2.32-11.18,1.54l0.97-5.34c1.97-10.12,4.4-22.57,13.62-22.87 c0.67-0.03,3.13,0.03,3.19,1.66c0.02,0.54-0.12,0.68-0.76,1.93c-0.65,0.97-0.89,1.8-0.86,2.74c0.09,2.58,2.05,4.28,4.9,4.18 c3.8-0.13,4.9-3.83,4.83-5.73C99.25,25.35,94.54,22.53,88.3,22.73L88.3,22.73z"/></g></svg>';
        } elseif (strtolower($type) === 'typo3') {
            return '<svg width="20px" height="20px" viewBox="-2 0 260 260" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid"><g><path d="M109.525333,4.05333333 C104.810667,8.08533333 101.461333,12.8 101.461333,26.88 C101.461333,65.1946667 149.824,180.288 182.762667,180.288 C186.462268,180.338187 190.147176,179.812799 193.685333,178.730667 L193.616,178.75 L192.774258,180.100166 C164.346546,225.411559 130.133077,258.650903 109.429541,259.317782 L108.8,259.328 C63.8293333,259.328 0,123.562667 0,63.8293333 C0,54.4213333 2.13333333,47.04 5.376,42.4533333 C20.8426667,23.552 69.2053333,8.74666667 109.525333,4.05333333 Z M172.672,0 C214.314667,0 256,6.72 256,30.2293333 C256,77.9306667 225.749333,135.744 210.304,135.744 C182.762667,135.744 148.437333,59.136 148.437333,20.8213333 C148.437333,3.34933333 155.136,0 172.608,0 L172.672,0 Z" fill="#F49700"></path></g></svg>';
        } elseif (strtolower($type) === 'drupal') {
            return '<svg height="20px" id="drupal" style="enable-background:new 0 0 55.999 63.998;" version="1.1" viewBox="0 0 55.999 63.998" width="20px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Drupal_Logo"><g><path d="M41.938,11.9c-3.269-2.034-6.352-2.836-9.436-4.871    C30.59,5.734,27.938,2.652,25.718,0c-0.432,4.256-1.727,5.982-3.206,7.215c-3.146,2.466-5.119,3.206-7.833,4.686    C12.396,13.071,0,20.471,0,36.376c0,15.907,13.384,27.622,28.246,27.622c14.863,0,27.753-10.791,27.753-27.128    C55.999,20.531,43.911,13.134,41.938,11.9z M42.294,58.195c-0.308,0.308-3.146,2.28-6.476,2.589s-7.832,0.493-10.546-1.973    c-0.432-0.432-0.309-1.049,0-1.295c0.309-0.247,0.555-0.432,0.925-0.432s0.309,0,0.493,0.122c1.233,0.988,3.084,1.789,7.031,1.789    s6.723-1.109,7.956-2.035c0.555-0.431,0.801-0.061,0.863,0.186C42.603,57.394,42.726,57.763,42.294,58.195z M31.502,52.585    c0.678-0.617,1.788-1.604,2.836-2.035c1.049-0.432,1.604-0.37,2.591-0.37c0.986,0,2.035,0.062,2.775,0.555    c0.74,0.494,1.172,1.604,1.418,2.221c0.247,0.616,0,0.986-0.493,1.232c-0.432,0.246-0.493,0.123-0.925-0.679    c-0.433-0.802-0.802-1.603-2.961-1.603c-2.158,0-2.837,0.74-3.885,1.603c-1.049,0.863-1.419,1.171-1.789,0.679    C30.699,53.693,30.823,53.201,31.502,52.585z M46.004,52.002c-2.221-0.186-4.82-6.015-7.656-6.138    c-3.578-0.124-11.349,7.46-17.454,7.46c-3.7,0-4.81-0.556-6.044-1.356c-1.85-1.295-2.775-3.269-2.713-5.98    c0.062-4.81,4.563-9.31,10.237-9.37c7.216-0.063,12.211,7.151,15.85,7.089c3.083-0.062,9.004-6.104,11.902-6.104    c3.084,0,3.878,2.487,3.878,4.398s-2.521,5.842-4,7.999C48.523,52.159,47.73,52.124,46.004,52.002z" style="fill-rule:evenodd;clip-rule:evenodd;fill:#48A0DC;"/></g></g><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/><g/></svg>';
        }
    }

    /**
     * @param \MoveElevator\FeatureIndex\Model\Entry $entry
     * @return string
     */
    private function renderLastUpdated(Entry $entry): string
    {
        return "<kbd data-tooltip='Last modification date'>" . $entry->getLastUpdated() . "</kbd>";
    }

    /**
     * @param \MoveElevator\FeatureIndex\Model\Entry $entry
     * @return string
     */
    private function renderIssueData(Entry $entry): string
    {
        $entryUtility = new EntryUtility();
        $jiraIcon = "<svg xmlns='http://www.w3.org/2000/svg'  viewBox='0 0 30 30' width='16px' height='16px'><path d='M 15 2.59375 C 12.613 5.01075 12.598 8.9300312 15 11.332031 L 18.667969 15 L 16.414062 17.253906 C 18.151062 18.991906 18.931625 21.350625 18.765625 23.640625 L 23.037109 19.369141 L 26.712891 15.693359 C 27.096891 15.310359 27.095891 14.689641 26.712891 14.306641 L 19.369141 6.9628906 L 15 2.59375 z M 11.234375 6.359375 L 6.9628906 10.630859 L 6.8398438 10.755859 L 3.2890625 14.304688 C 2.9060625 14.688688 2.9060625 15.309359 3.2890625 15.693359 L 13.966797 26.371094 L 15 27.40625 C 17.387 24.98925 17.402 21.069969 15 18.667969 L 11.332031 15 L 13.585938 12.746094 C 11.848937 11.008094 11.068375 8.649375 11.234375 6.359375 z'/></svg>";
        $issueType = !empty($entry->getIssueData()) ? " <span data-tooltip='Jira issue type: " . $entry->getIssueData()['type']['name'] . "'><img src='" . $entry->getIssueData()['type']['icon'] . "'/></span>" : "";
        $issueStatus = !empty($entry->getIssueData()) ? "<span class='status " . $entry->getIssueData()['status']['color'] . "' data-tooltip='Jira issue status: " . $entry->getIssueData()['status']['name'] . "'>" . $entry->getIssueData()['status']['name'][0] . "</span>" : "";
        $issueAssigneeInitialies = !empty($entry->getIssueData()) ? implode('', array_map(function($value) { return substr($value, 0, 1); }, explode(' ', $entry->getIssueData()['assignee']['name']))) : "";
        $issueAssignee = !empty($entry->getIssueData()) ? "<span class='pill person' data-tooltip='Jira issue assignee: " . $entry->getIssueData()['assignee']['name'] . "'>" . $issueAssigneeInitialies . "</span>" : "";
        return $entry->getIssue() ? "<a class='pill' href='" . $entryUtility->getIssueLink($entry) . "' target='_blank'>$jiraIcon " . "<span data-tooltip='Jira issue: " . $entry->getIssue() . "' >" . $entry->getIssue() . "</span>$issueType$issueStatus$issueAssignee</a>" : "";
    }

}
