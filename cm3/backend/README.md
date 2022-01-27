Download dependencies
 php composer.phar  install

Refresh the autoloader classes:
php composer.phar  dump-autoload


Token data, unpacked:

  contact ID
  Global permission string, count byte
    Event ID int
    Special Permission bytes in the form of enums
      Permission set 1
        Attendee view
        Attendee Edit
        Attendee Export
        Attendee types manage, email templates, questions
        Attendee refund
        Badge statistics
        Manage venue map, locations
      Permission set 2
        Check in
        Print one-off badges
        Manage badge formats
        Payment request view
        Payment request Create/Cancel
        Payment request Edit
      Permission set 3
        Manage banlist
        Manage users
        Staff apps view
        Staff apps Review/Assign
        Staff apps Edit
        Staff apps export
        Staff apps manage types (including org chart), email templates, questions        

  Application group Permission string, count byte
    Application group ID (or 0 for attendees), int
    Permission byte in the form of a flag enum
      View
      Review/Assign
      Edit
      Export data
      Manage types, email templates, questions
