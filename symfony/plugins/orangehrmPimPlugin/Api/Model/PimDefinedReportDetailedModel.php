<?php

namespace OrangeHRM\Pim\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Core\Service\ReportGeneratorService;
use OrangeHRM\Entity\Report;

class PimDefinedReportDetailedModel implements Normalizable
{
    /**
     * @var ReportGeneratorService|null
     */
    protected ?ReportGeneratorService $reportGeneratorService = null;

    /**
     * @var Report
     */
    private Report $report;

    /**
     * @return ReportGeneratorService
     */
    protected function getReportGeneratorService(): ReportGeneratorService
    {
        if (!$this->reportGeneratorService instanceof ReportGeneratorService) {
            $this->reportGeneratorService = new ReportGeneratorService();
        }
        return $this->reportGeneratorService;
    }

    /**
     * @param Report $report
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    /**
     * @return Report
     */
    public function getReport(): Report
    {
        return $this->report;
    }

    public function toArray(): array
    {
        $detailedReport = $this->getReport();
        $selectedFilterFields = $this->getReportGeneratorService()
            ->getReportGeneratorDao()
            ->getSkippedSelectedFilterFieldsByReportId($detailedReport->getId());
        $selectedDisplayFieldGroups = $this->getReportGeneratorService()
            ->getReportGeneratorDao()
            ->getDisplayFieldGroupIdList($detailedReport->getId());

        $criteria = [];
        foreach ($selectedFilterFields as $key => $value) {
            $criteria[$value->getFilterField()->getId()] = [
                "x" => $value->getX(),
                "y" => $value->getY(),
                "operator" => $value->getOperator(),

            ];
        }

        $fieldGroup = [];
        foreach ($selectedDisplayFieldGroups as $selectedDisplayFieldGroup) {
            $fieldGroup[$selectedDisplayFieldGroup] = [
                'fields' => $this->getReportGeneratorService()
                    ->getReportGeneratorDao()
                    ->getSelectedDisplayFieldIdByReportGroupId(
                        $detailedReport->getId(),
                        $selectedDisplayFieldGroup
                    ),
                'includeHeader' => count(
                        $this->getReportGeneratorService()->getReportGeneratorDao()->isIncludeHeader(
                            $detailedReport->getId(),
                            $selectedDisplayFieldGroup
                        )
                    ) == 1
            ];
        }

        $selectedFilterFieldOperator = $this->getReportGeneratorService()
            ->getReportGeneratorDao()
            ->getIncludeType($detailedReport->getId())
            ->getOperator();
        $includeType = ($selectedFilterFieldOperator === 'isNull') ? 'onlyCurrent' : (($selectedFilterFieldOperator === 'isNotNull') ? 'onlyPast' : 'currentAndPast');

        return [
            'id' => $detailedReport->getId(),
            'name' => $detailedReport->getName(),
            'include' => $includeType,
            'criteria' => $criteria,
            'fieldGroup' => $fieldGroup
        ];
    }
}