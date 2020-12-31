<?php

namespace WHMCS\Module\Notification\Telegram;

use WHMCS\Module\Notification\DescriptionTrait;
use WHMCS\Module\Contracts\NotificationModuleInterface;
use WHMCS\Notification\Contracts\NotificationInterface;

/**
 * Telegram Notification Module
 *
 * All notification modules must implement NotificationModuleInterface
 */
class Telegram implements NotificationModuleInterface
{
    use DescriptionTrait;

    /**
     * Constructor
     *
     * Any instance of a notification module should have the display name and
     * logo filename at the ready.  Therefore it is recommend to ensure these
     * values are set during object instantiation.
     *
     * The telegram notification module utilizes the DescriptionTrait which
     * provides methods to fulfill this requirement.
     *
     * @see \WHMCS\Module\Notification\DescriptionTrait::setDisplayName()
     * @see \WHMCS\Module\Notification\DescriptionTrait::setLogoFileName()
     */
    public function __construct()
    {
        $this->setDisplayName('Telegram Notification Module')
            ->setLogoFileName('logo.png');
    }

    /**
     * Settings required for module configuration
     *
     * The method should provide a description of common settings required
     * for the notification module to function.
     *
     * For example, if the module connects to a remote messaging service this
     * might be username and password or OAuth token fields required to
     * authenticate to that service.
     *
     * This is used to build a form in the UI.  The values submitted by the
     * admin based on the form will be validated prior to save.
     * @see testConnection()
     *
     * The return value should be an array structured like other WHMCS modules.
     * @link https://developers.whmcs.com/payment-gateways/configuration/
     *
     * @return array
     */
    public function settings()
    {
        return [
            'botToken' => [
                'FriendlyName' => 'Telegram Bot API Token',
                'Type' => 'text',
                'Description' => 'You can create token at <a target="_blank" href="https://t.me/BotFather">BotFather</a>',
            ]
        ];
    }

    /**
     * Validate settings for notification module
     *
     * This method will be invoked prior to saving any settings via the UI.
     *
     * Leverage this method to verify authentication and/or authorization when
     * the notification service requires a remote connection.
     *
     * In the event of failure, throw an exception. The exception will be displayed
     * to the user.
     *
     * @param array $settings
     *
     * @throws \Exception
     */
    public function testConnection($settings)
    {
        // Check to ensure bot token were provided and valid
        if (empty($settings['botToken']) || strpos($settings['botToken'], ':') === false) {
            throw new \Exception('Token is empty or invalid, please check and try again.');
        }

        // Perform API call here to validate the supplied API username and password.
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', "https://api.telegram.org/bot{$settings['botToken']}/getUpdates");
        if ($response->getStatusCode() !== 200) {
            throw new \Exception((string)$response->getBody());
        }
        // Return an exception if the connection fails.
    }

    /**
     * The individual customisable settings for a notification.
     *
     * These settings are provided to the user whilst configuring individual notification rules.
     *
     * The "Type" of a setting can be text, password, yesno, dropdown, radio, textarea and dynamic.
     *
     * @see getDynamicField for how to obtain dynamic values
     *
     * @return array
     */
    public function notificationSettings()
    {
        return [
            'chatID' => [
                'FriendlyName' => 'Telegram Chat ID',
                'Type' => 'text',
                'Description' => 'You can get it from @GetIDsBot or @RawDataBot',
                'Required' => true,
            ]
        ];
    }

    /**
     * The option values available for a 'dynamic' Type notification setting
     *
     * @see notificationSettings()
     *
     * @param string $fieldName Notification setting field name
     * @param array $settings Settings for the module
     *
     * @return array
     */
    public function getDynamicField($fieldName, $settings)
    {
//        if ($fieldName == 'channel') {
//            return [
//                'values' => [
//                    [
//                        'id'          => 1,
//                        'name'        => 'Tech Support',
//                        'description' => 'Channel ID',
//                    ],
//                    [
//                        'id'          => 2,
//                        'name'        => 'Customer Service',
//                        'description' => 'Channel ID',
//                    ],
//                ],
//            ];
//        }

        return [];
    }

    /**
     * Deliver notification
     *
     * This method is invoked when rule criteria are met.
     *
     * In this method, you should craft an appropriately formatted message and
     * transmit it to the messaging service.
     *
     * WHMCS provides a getAttributes method via $notification here. This method returns a NotificationAttributeInterface
     * object which allows you to obtain key data for the Notification.
     *
     * @param NotificationInterface $notification A notification to send
     * @param array $moduleSettings Configured settings of the notification module
     * @param array $notificationSettings Configured notification settings set by the triggered rule
     *
     * @throws \Exception on error of sending notification
     *
     * @see https://classdocs.whmcs.com/7.8/WHMCS/Notification/Contracts/NotificationInterface.html
     * @see https://classdocs.whmcs.com/7.8/WHMCS/Notification/Contracts/NotificationAttributeInterface.html
     */
    public function sendNotification(NotificationInterface $notification, $moduleSettings, $notificationSettings)
    {
        if (!$notificationSettings['chatID']) {
            // Abort the Notification.
            throw new \Exception('No chat ID for notification delivery.');
        }

//        $notificationData = [
//            'notification_title'      => $notification->getTitle(),
//            'notification_url'        => $notification->getUrl(),
//            'notification_message'    => $notification->getMessage(),
//            'notification_attributes' => [],
//        ];
//
//        foreach ($notification->getAttributes() as $attribute) {
//            $notificationData['notification_attributes'][] = [
//                'label' => $attribute->getLabel(),
//                'value' => $attribute->getValue(),
//                'url'   => $attribute->getUrl(),
//                'style' => $attribute->getStyle(),
//                'icon'  => $attribute->getIcon(),
//            ];
//        }

        // Perform API call to your notification provider.
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', "https://api.telegram.org/bot{$moduleSettings['botToken']}/sendMessage", [
            'http_errors' => false,
            'form_params' => [
                'chat_id' => $notificationSettings['chatID'],
                'text' => "<b>{$notification->getTitle()}</b>\n\n{$notification->getMessage()}\n\n{$notification->getUrl()}",
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            // The API returned an error. Perform an action and abort the Notification.
            throw new \Exception((string)$response->getBody());
        }
    }
}
