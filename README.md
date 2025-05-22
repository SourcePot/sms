# Connecting to the MessageBird service, Datapool App & Transmitter

The Sms.php class implements the Datapool `Transmitter` and `App` interface. The App is part of the admin category. 

## The App

The App provides the Datapool administrator with HTML-forms for:

* editing credentials to access MessageBird, 
* checking the balance with MassageBird and 
* sending SMS Messages

See the following Datapool screenshot:

<img src="./assets/app.png" alt="SMS admin page" style="width:100%"/>

## The Transmitter

The transmitter implemented by the Sms.php class is available wherever data needs to be sent out of Datappol. E.g. the Datapool processor "OutboxEntries" provides access to all registered transmitters. See the following example:

<img src="./assets/transmitter.png" alt="sample transmitter use" style="width:100%"/>
