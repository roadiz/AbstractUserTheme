<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\NodesSources;

trait ManualSeoTrait
{
    abstract protected function getPageTitle(): string;

    /**
     * @param NodesSources|null $fallbackNodeSource
     *
     * @return array
     */
    public function getNodeSEO(NodesSources $fallbackNodeSource = null)
    {
        return [
            'title' => $this->getPageTitle() . ' — ' . $this->get('settingsBag')->get('site_name'),
            'description' => $this->getPageTitle(),
            'keywords' => '',
        ];
    }

    /**
     * Extends theme assignation with custom data.
     *
     * Override this method in your theme to add your own service
     * and data.
     */
    protected function extendAssignation()
    {
        parent::extendAssignation();

        $this->assignation['title'] = $this->getPageTitle();
    }
}
