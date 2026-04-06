# SMS Transmitter employing MessageBird API

The *SourcePot\Sms\Sms*-class implements the Datapool `Transmitter` and `Job` interface. It is configurated by the "*Admin → Transmitter*"-App. The SMS job method creates a signal for the prepaid value.

## The *SourcePot\Sms\Sms*-class

The *SourcePot\Sms\Sms*-class provides HTML-forms for:

* Editing credentials to access MessageBird, 
* Checking the balance with MassageBird and 
* Sending SMS Messages

See the following Datapool screenshot:

![SMS Transmitter](/assets/admin_sms_transmitter.png "SMS admin page")

## The Transmitter of the *SourcePot\Sms\Sms*-class

The transmitter implemented by the *SourcePot\Sms\Sms*-class is available within Datapool in every context employing Transmitte such as the *OutboxEntries*-processor or signal trigger.