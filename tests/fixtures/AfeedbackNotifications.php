<?php
/**
 * Some example SES Feedback Notification messages.
 * Documented by Amazon at @link http://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
 */
return array(

    // The following is an example of a Permanent bounce notification without a delivery status notification (DSN):
    'bounceMessage1' => array(
        'notificationType' => 'Bounce',
        'bounce' => array(
            'bounceType' => 'Permanent',
            'bounceSubType' => 'General',
            'bouncedRecipients' => array(
                0 => array('emailAddress' => 'recipient1@example.com',),
                1 => array('emailAddress' => 'recipient2@example.com',),
            ),
            'timestamp' => '2013-04-25T14:15:06.000Z',
            'feedbackId' => '00000137860315fd-869464a4-8680-4114-98d3-716fe35851f9-000000',
        ),
        'mail' => array(
            'timestamp' => '2013-04-25T14:15:05.000Z',
            'messageId' => '0000013e418c2aab-c1ba5d45-2d9f-4844-9b74-2b57ef3a7700-000000',
            'source' => 'email_1337983178237@amazon.com',
            'destination' => array(
                "recipient1@example.com",
                "recipient2@example.com",
                "recipient3@example.com",
                "recipient4@example.com",
            ),
        ),
    ),

    // The following is an example of a Permanent bounce notification that has a delivery status notification (DSN):
    'bounceMessage2' => array(
        'notificationType' => 'Bounce',
        'bounce' => array(
            'bounceType' => 'Permanent',
            "reportingMTA"=>"dns; email.example.com",
            'bounceSubType' => 'General',
            'bouncedRecipients' => array(
                0 => array(
                    'emailAddress' => 'username@example.com',
                    'status' => '5.1.1',
                    'action' => 'failed',
                    'diagnosticCode' => 'smtp; 550 5.1.1 <username@example.com>... User',
                ),
            ),
            'timestamp' => '2013-04-25T14:15:06.000Z',
            'feedbackId' => '00000137860315fd-869464a4-8680-4114-98d3-716fe35851f9-000000',
        ),
        'mail' => array(
            'timestamp' => '2013-04-25T14:15:05.000Z',
            'messageId' => '0000013e418c2aab-c1ba5d45-2d9f-4844-9b74-2b57ef3a7700-000000',
            'source' => 'email_1337983178237@amazon.com',
            'destination' => array(
                "username@example.com",
            ),
        ),
    ),

    // The following is an example of a Transient bounce notification that has a delivery status notification (DSN):
    'bounceMessage3' => array(
        'notificationType' => 'Bounce',
        'bounce' => array(
            'reportingMTA' => 'dsn; aws-ses-mta-svc-iad-1030.vdc.amazon.com',
            'bounceType' => 'Transient',
            'bouncedRecipients' => array(
                0 => array(
                    'emailAddress' => 'joeblogss@baddomainnameexample.com',
                    'status' => '5.1.2',
                    'diagnosticCode' => 'smtp; 553 5.1.2 Unknown mail server. Could not find a mail server for baddomainnameexample.com',
                    'action' => 'failed',
                ),
            ),
            'bounceSubType' => 'General',
            'timestamp' => '2013-04-25T14:15:06.000Z',
            'feedbackId' => '0000013e418c309c-87ab2427-adb2-11e2-8c99-4d51802b74c2-000000',
        ),
        'mail' => array(
            'timestamp' => '2013-04-25T14:15:05.000Z',
            'source' => 'noreply@pph.me',
            'messageId' => '0000013e418c2aab-c1ba5d45-2d9f-4844-9b74-2b57ef3a7700-000000',
            'destination' => array(0 => 'joeblogss@baddomainnameexample.com'),
        ),
    ),

    // The following is an example of a complaint notification without a feedback report:
    'complaintMessage1'=>array(
        "notificationType"=>"Complaint",
        "complaint"=>array(
            "complainedRecipients"=>array(
                0 => array(
                    "emailAddress"=>"recipient1@example.com"
                ),
            ),
            "timestamp"=>"2012-05-25T14:59:38.613-07:00",
            "feedbackId"=>"0000013786031775-fea503bc-7497-49e1-881b-a0379bb037d3-000000"
        ),
        "mail"=> array(
            "timestamp"=>"2012-05-25T14:59:38.613-07:00",
            "messageId"=>"0000013786031775-163e3910-53eb-4c8e-a04a-f29debf88a84-000000",
            "source"=>"email_1337983178613@amazon.com",
            "destination"=> array(
                "recipient1@example.com",
                "recipient2@example.com",
                "recipient3@example.com",
                "recipient4@example.com"
            ),
        ),
    ),

    // The following is an example of a complaint notification that has a feedback report:
    'complaintMessage2'=>array(
        "notificationType"=>"Complaint",
        "complaint"=>array(
            "userAgent"=>"Comcast Feedback Loop (V0.01)",
            "complainedRecipients"=>array(
                0 => array(
                    "emailAddress"=>"recipient1@example.com"
                ),
            ),
            "complaintFeedbackType"=>"abuse",
            "arrivalDate"=>"2009-12-03T04:24:21.000-05:00",
            "timestamp"=>"2012-05-25T14:59:38.613-07:00",
            "feedbackId"=>"0000013786031775-fea503bc-7497-49e1-881b-a0379bb037d3-000000"
        ),
        "mail"=> array(
            "timestamp"=>"2012-05-25T14:59:38.613-07:00",
            "messageId"=>"0000013786031775-163e3910-53eb-4c8e-a04a-f29debf88a84-000000",
            "source"=>"email_1337983178613@amazon.com",
            "destination"=> array(
                "recipient1@example.com",
                "recipient2@example.com",
                "recipient3@example.com",
                "recipient4@example.com"
            ),
        ),
    ),
);
