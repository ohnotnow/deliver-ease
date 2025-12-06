I want to create a mobile-focussed app called 'DeliverEase'.  It's main purpose is to allow small, local businesses
which do their own deliveries keep their customers notified about deliveries which are out that day.

On the business owner screen - they should be able to create a new 'delivery run'.  This gives them a text box
where they can paste in a list of email addresses - one per line - and a name to go with it (the name could be an address, the customer name - whatever - and is optional).  And there should be a regular text box so that
they can give this 'run' a name of their choosing.  

On their main screen there should be a list of 'runs' with the
name, the date it was created, and an edit button which takes them to the pre-filled data in the same screen as the
one to create a new run.

They should also have a 'share' button next to each run which will allow them to give the 'run' to a driver.  The
share link should use a unique UUID style to make it difficult to guess.

On the delivery driver side - they are given the share link and when they open it they see a simple screen which is
a little like a 'TODO' app.  The controls should be large and easy to use for a rushed, busy driver using a mobile phone.  There is a friendly list of the name (if provided) with a fallback of the email addresses and a 'Complete' button next to each.  Above the list
there should also be a 'Start' button.

When the driver clicks the 'Start' button - an email will be sent to the first and second people on the 'run'.  This will
notify them that they are next for delivery - or one stop away respectively.

Then when the driver marks the first delivery as completed.  That visually
marks the delivery as done and also emails the third person on the list to let them know they are one stop away.

This process continues until the whole list is processed.

On the admin page they should be able to see the list and the completed indicator - so they can keep track of how the deliveries
are going.

