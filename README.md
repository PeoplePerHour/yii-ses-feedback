Yii-ses-feedback
================

Introduction
------------

Does your Yii application use Amazon [Simple Email Service (SES)](http://aws.amazon.com/ses/) for sending email?
If so you can use this extension to process "feedback notifications" such as email bounces and email complaints.

What do we mean by a "email bounce"? We mean a email delivery failure, for example, if an email address does not exist.

What do we mean by a "email complaint"? We mean the recipient has indicated they do not what the email that you sent them, for example, by clicking "Mark as spam" in their email client. Amazon SES has feedback loops set up with certain major ISPs, and can automatically forward the complaint information to us.

Why do you need Yii-ses-feedback?
---------------------------------

 * Cost: By using SES, you pay per message. To save money you need a feedback loop so you can know which emails are not being recieved, and stop sending them.
 * Reputation: If your SES account racks up too many bounces or complaints, your reputation will decrease. Amazon might not let you increase your sending throughput for example.
 * Workflow: You might want to deactivate users with invalid email addresses. For many apps, having a large number of dormant user accounts will we a performance problem.
 * Stats: You might want to record bounce stats in your application database.


Requirements
------------

 * Yii 1.0+
 * You must be using Amazon [Simple Email Service](http://aws.amazon.com/ses/) (SES) to send email.
  * You must configure Amazon SES Feedback Notifications via Amazon Simple Notification Service (SNS).
    Here are [instructions](http://docs.aws.amazon.com/ses/latest/DeveloperGuide/configure-sns-notifications.html).
  * You must configure the SNS messages to integrate with Amazon Simple Queue Service (SQS) to ensure the feedback notifications are stored until our application can process them.
    Set up the following AWS components to handle bounce notifications:
     1. Create an Amazon SQS queue named `bounces_and_complaints`.
     1. Create an Amazon SNS topic named `bounces_and_complaints-topic`.
     1. Configure the new Amazon SNS topic to publish to the SQS queue.
     1. Configure Amazon SES to publish bounce notifications using `bounces_and_complaints-topic` to the `bounces_and_complaints` queue. Instead of a combined queue, you can choose to use two separate queues if you want.
 * You must must use the [AWS SDK for PHP 2](https://github.com/aws/aws-sdk-php) within your Yii App. This is used to access the SQS API.
 * You must have the access credentials to use the SQS API. They will be needed when configuring Yii-ses-feedback.


Features
--------

 * Instead of parsing bounced emails ourselves to determine the cause, we can let Amazon SES do it for us.
   Amazon SES will categorize your hard bounces into two types: permanent and transient.
   A permanent bounce indicates that you should never send to that recipient again.
   A transient bounce indicates that the recipient's ISP is not accepting messages for that particular recipient at that time and you can retry delivery in the future.
   The amount of time you should wait before resending to the address that generated the transient bounce depends on the transient bounce type.
   Certain transient bounces require manual intervention before the message can be delivered (e.g., message too large or content error).
   If the bounce type is undetermined, you should manually review the bounce and act accordingly.
 * Raise a Yii event whenever a Email bounce or complaint is found.
   It's up to you how you handle the event (an example is given).


Installation - Manual (The Old Fashion Way)
-------------------------------------------

Extract the package to your `extensions` folder into a directory called `yii-ses-feedback`.

Ensure the extension files will be autoloaded by adding a line to the Yii imports config section:

```php
<?php
return array(
    'import' => array(
        // ...
        'ext.yii-ses-feedback.*',
    ),
```

Add the component to your Yii console app config file (e.g. `your-project/protected/config/console.php`) and define each queue.
If you have multiple queues, configure multiple handlers.

```php
<?php
return array(
    // ...
    'components'=>array(
        // ...
        'sesFeedback' => array(
            'class' => 'ext.yii-ses-feedback.ASesFeedback',
            'handlers' => array(
                'bounceAndComplaint' => array(
                    'class'  => 'ext.yii-ses-feedback.ASesFeedbackHandler',
                    'accessKey'  => 'DKIASHFUSET2X3G5JR5D',
                    'secretKey'  => 'fAXKA2GdslDlGXIbdZNty4Ag4eig453yOfFuffr4',
                    'region'     => 'us-east-1',
                    'queueName'  => 'bounces_and_complaints',
                ),
            )
        ),
        // ...
    ),
    // ...
);
```

Now we need a way to trigger the command. Copy the `examples/ExampleSesFeedbackCommand.php` file into your `commands` folder and modify it as you need for your application.


Installation - Automatic (using Composer)
-----------------------------------------

The advantage of using composer is that you wont need to change your Yii import map, the composer autoloader will import the needed files.

Add `peopleperhour/yii-ses-feedback` as a dependency in your project's `composer.json` file:
<pre>
{
    "require": {
        ...
        "peopleperhour/yii-ses-feedback": "dev-master"
    },
</pre>

Download and install Composer.

<pre>
curl -s "http://getcomposer.org/installer" | php
</pre>

Install your dependencies.

<pre>
php composer.phar update
</pre>

Require Composer's autoloader. Composer prepares an autoload file that's capable of autoloading all of the classes in any of the libraries that it downloads.
To use it, just add the following line to your code's bootstrap process.

<pre>
require '/path/to/vendor/autoload.php';
</pre>

You can find out more on how to install Composer, configure autoloading, and other best-practices for defining dependencies at [getcomposer.org](http://getcomposer.org/).

To configure the extension, add a `sesFeedback` component to your Yii console app config file (e.g. `your-project/protected/config/console.php`) and define each queue.
If you have multiple queues, configure multiple handlers.

```php
<?php
return array(
    // ...
    'components'=>array(
        // ...
        'sesFeedback' => array(
            'class' => '\ASesFeedback',                     // Composer autoloading needs to a prepended backslash
            'handlers' => array(
                'bounceAndComplaint' => array(
                    'class'  => '\ASesFeedbackHandler',     // Composer autoloading needs to a prepended backslash
                    'accessKey'  => 'DKIASHFUSET2X3G5JR5D',
                    'secretKey'  => 'fAXKA2GdslDlGXIbdZNty4Ag4eig453yOfFuffr4',
                    'region'     => 'us-east-1',
                    'queueName'  => 'bounces_and_complaints',
                ),
            )
        ),
        // ...
    ),
    // ...
);
```

Running unit tests
------------------

The unit tests do not use a real SQS queue, instead they use a Mock queue implemented as a PHP Array.

In your `tests/config.php` file, ensure the extension files will be imported and that the sesFeedback component uses the Mock Version:

```php
<?php
return array(
    // ...
    'import' => array(
        // ...
        'application.extensions.yii-ses-feedback.*',   // Not needed if using composer auto-loader
    ),
    // ...
    'components'=>array(
        // ...
        'sesFeedback' => array(
            'class' => 'ext.yii-ses-feedback.ASesFeedback',  // or '\ASesFeedback' if using the composer autoloader.
            'handlers' => array(
                'myMockQueue' => array(
                    'class'  => 'ext.yii-ses-feedback.tests.mocks.ASesFeedbackHandlerMock',   // or '\ASesFeedbackHandlerMock'
                ),
            )
        ),
    ),
);
```

Go to your application tests directory, usually `protected/tests` and run the following command:

<pre>
phpunit --verbose ../extensions/yii-ses-feedback/tests/unit/
or
phpunit --verbose ../vendor/peopleperhour/yii-ses-feedback/tests/unit/
</pre>

This will run the unit tests, if all went well they should all pass, otherwise please check your configuration.


How to schedule automatic processing
------------------------------------

To automatically process your feedback queue periodically, set up a cron job to run the command. Example:

<pre>
17 3 * * * /usr/bin/php /var/www/yourapp/yiic.php exampleSesFeedback >/dev/null 2>&1
</pre>

Another way is to use the Yii [phpdoc-crontab extension](http://www.yiiframework.com/extension/phpdoc-crontab/).
Once configured, you will be able to define automated Yii commands with some simple PHP header comments, Example:

<pre>
/**
 * @cron *\10 * * * *
 * @cron-tags live staging
 */
public function actionIndex($maxNum=1000,$leaveFailuresInQueue=false)
{
    return parent::actionIndex($maxNum,$leaveFailuresInQueue);
}
</pre>

Resources
---------

 * Instructions to configure SNS notifications - http://docs.aws.amazon.com/ses/latest/DeveloperGuide/configure-sns-notifications.html
 * AWS SDK PHP 2 for SES: http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-ses.html
 * AWS SDK PHP 2 for SQS: http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-sqs.html

