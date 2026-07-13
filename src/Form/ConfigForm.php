<?php declare(strict_types=1);

namespace AnalyticsSnippetPiwik\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;

class ConfigForm extends Form
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'analyticssnippetpiwik_tracker_url',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Matomo tracker api url', // @translate
                ],
                'attributes' => [
                    'id' => 'analyticssnippetpiwik_tracker_url',
                    'placeholder' => 'https://stats.example.com/matomo.php',
                ],
            ])
            ->add([
                'name' => 'analyticssnippetpiwik_site_id',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Matomo site id', // @translate
                ],
                'attributes' => [
                    'id' => 'analyticssnippetpiwik_site_id',
                    'placeholder' => '1',
                ],
            ])
            ->add([
                'name' => 'analyticssnippetpiwik_token_auth',
                'type' => CommonElement\Secret::class,
                'options' => [
                    'label' => 'Matomo token authentication', // @translate
                    'info' => 'API token with at least Admin permission in order to save visitor ip. The token is stored encrypted when a secret key is set.', // @translate
                    'documentation' => 'https://matomo.org/faq/general/faq_114/',
                ],
                'attributes' => [
                    'id' => 'analyticssnippetpiwik_token_auth',
                    'placeholder' => '4fbb1496d7b563acbfe08a6f07a061c5',
                ],
            ])
        ;
    }
}
