<?php
namespace selvinortiz\doxter;

use yii\base\Event;

use Craft;
use craft\base\Plugin;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;

use selvinortiz\doxter\fields\DoxterField;
use selvinortiz\doxter\models\SettingsModel;
use selvinortiz\doxter\services\DoxterService;
use selvinortiz\doxter\variables\DoxterVariable;
use selvinortiz\doxter\extensions\DoxterExtension;
use selvinortiz\doxter\assetbundles\DoxterPluginAssetBundle;

/**
 * Class Doxter
 *
 * @package selvinortiz\doxter;
 *
 * @property DoxterService $api
 */
class Doxter extends Plugin
{
    /**
     * @param string|array $message
     */
    public function info($message)
    {
        Craft::info($message, Doxter::class);
    }

    /**
     * @param string|array $message
     */
    public function warning($message)
    {
        Craft::warning($message, Doxter::class);
    }

    /**
     * @param string|array $message
     */
    public function error($message)
    {
        Craft::error($message, Doxter::class);
    }

    public function init()
    {
        parent::init();

        Craft::$app->view->registerTwigExtension(new DoxterExtension());

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event)
            {
                $event->types[] = DoxterField::class;
            }
        );

        if (class_exists(\markhuot\CraftQL\CraftQL::class))
        {
            Event::on(
                DoxterField::class,
                'craftQlGetFieldSchema',
                function($event)
                {
                    $event->handled = true;

                    $outputSchema = $event->schema->createObjectType(ucfirst($event->sender->handle).'DoxterFieldData');

                    $outputSchema->addStringField('text')
                        ->resolve(function($root) {
                            return (string)$root->getRaw();
                        });

                    $outputSchema->addStringField('html')
                        ->resolve(function($root) {
                            return (string)$root->getHtml();
                        });

                    $event->schema->addField($event->sender)->type($outputSchema);
                }
            );
        }

        $this->name          = $this->getSettings()->pluginAlias;
        $this->hasCpSection  = $this->getSettings()->enableCpTab;
        $this->hasCpSettings = true;
    }

    /**
     * @return SettingsModel
     */
    public function createSettingsModel()
    {
        return new SettingsModel();
    }

    /**
     * @return string|null
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function settingsHtml()
    {
        $settings  = $this->getSettings();
        $variables = [
            'plugin'   => $this,
            'settings' => $settings,
        ];

        Craft::$app->getView()->registerAssetBundle(DoxterPluginAssetBundle::class);

        return Craft::$app->getView()->renderTemplate('doxter/_settings', $variables);
    }

    /**
     * @return string
     */
    public function defineTemplateComponent()
    {
        return DoxterVariable::class;
    }
}

/**
 * Allows me to use a more expressive syntax and have more control over type hints
 *
 * @return Doxter
 */
function doxter(): Doxter
{
    return Craft::$app->loadedModules[Doxter::class] ?? null;
}
