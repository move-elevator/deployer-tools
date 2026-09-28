<?php

namespace MoveElevator\FeatureIndex\Model;

class Entry
{
    protected string $name;
    protected Deployment $deployment;
    protected string $category;
    protected string $tag;
    protected string $issue;
    protected array $issueData = [];

    /**
     * @param string $name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDeployment(): Deployment
    {
        return $this->deployment;
    }

    public function setDeployment(Deployment $deployment): void
    {
        $this->deployment = $deployment;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return string
     */
    public function getIssue(): string
    {
        return $this->issue;
    }

    /**
     * @param string $issue
     */
    public function setIssue(string $issue): void
    {
        $this->issue = $issue;
    }

    /**
     * @return array
     */
    public function getIssueData(): array
    {
        return $this->issueData;
    }

    /**
     * @param array $issueData
     */
    public function setIssueData(array $issueData): void
    {
        $this->issueData = $issueData;
    }

}
