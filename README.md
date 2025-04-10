# UM Date Difference in Days
Extension to Ultimate Member to display number of days until/after a date from/to today either by UM Form fields or a Shortcode.

## UM Settings -> Appearance -> Profile -> "Date Difference in Days" 
1. *  meta_keys - Comma separated meta_keys to include in the "Date difference in days" formatting. 
This field is not used by the "date_diff_days" shortcode where the meta_key is defined in the meta_key parameter.
2.  *  Number of days until next birthday - Click to display number of days until the User's next birthday if birth_date meta_key selected.
This field is not used by the "date_diff_days" shortcode where it's always true.
3.  *  Max value of days before for display - Enter the max value for number of days to show until original value is displayed and Shortcode displays blank.
Default value if empty:   30
4.  *  Max value of days after for display  - Enter the max value for number of days to show after original value is displayed and Shortcode displays blank.
Default value if empty:   30
5. *  Date format - Enter your PHP date format for the "date value from the date meta_key" into the placeholder %s.
Default value if empty:   M j   - Shortcode "date_diff_days" parameter:   date_format
6. *  One day before - Enter your text where %s if inserted is replaced with the date value from the date meta_key.
Default value if empty:   one day before %s   - Shortcode "date_diff_days" parameter:   one_day_before
7. *  Days before - Enter your text where %d is number of days and %s is the date value from the date meta_key.
%d and %s may be inserted in any order or one or both omitted.
Default value if empty:   %d days before %s   - Shortcode "date_diff_days" parameter:   days_before
8.  *  One day after - Enter your text where %s if inserted is replaced with the date value from the date meta_key.
Default value if empty:   one day after %s   - Shortcode "date_diff_days" parameter:   one_day_after
9. *  Days after - Enter your text where %d is number of days and %s is the date value from the date meta_key.
%d and %s may be inserted in any order or one or both omitted.
Default value if empty:   %d days after %s   - Shortcode "date_diff_days" parameter:   days_after
10.  *  Today - Enter your text where %s if inserted is the date value from the date meta_key.
Default value if empty:   today %s   - Shortcode "date_diff_days" parameter:   today

## Shortcode - date_diff_days
Example for counting down to a birthday

<code>[date_diff_days meta_key="birth_date" days_before="Next birthday at %s is %d days from today" today="CELEBRATION DAY %s {display_name} {age} years old"]</code>

## Placeholders for all fields and shortcode
* {display_name}
* {first_name}
* {last_name}
* {gender}
* {username}
* {email},
* {site_name}
* {user_account_link}
* {age}

## Updates
None

## Installation and Updates
Download the plugin ZIP file at the green Code button. Install as a WP Plugin, activate the plugin.
