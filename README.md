# WPassword plugin

Configure and use strong password policies to ensure all your site users use strong passwords.

https://www.wpwhitesecurity.com/wordpress-plugins/password-policy-manager-wordpress/

# Plugin developers documentation
This is the plugin's developers documentation. In this section you can find information about the design of the plugin, its features and how everything works.

## Available filters/overrides

PPMWP offers some filterable values which can be used when testing the plugin. Here we provide the code you can use to quickly and easily configure them.

### Enable testing mode

This allows you to specify seconds/hours as an option for the Password Expiration policy.

````
add_filter( 'ppmwp_enable_testing_mode', 'custom_enable_testing_mode' );

function custom_enable_testing_mode( $enabled ) {
  $enabled = true;
  return $enabled;
}
````

### Custom in activity period.

Here you can specify in seconds the length of time a user needs to be dormant for to be marked as inactive.

````
add_filter( 'ppmwp_testing_mode_inactive_period', 'custom_testing_mode_inactive_period' );

function custom_testing_mode_inactive_period( $period ) {
  // Period in seconds.
  $period = 600;
  return $period;
}
````

## How to's

**How are "new users" determined on login?**

For a user to be classed as "new/first login" the user must have a valid key stored in `wp_usermeta` (key `ppmwp_new_user_register`) - this key is defined as the constant PPM_WP_META_NEW_USER.

We add this key during the `user_register` action by checking if the user's password policy has the setting "force new users to reset their password the first time they login" enabled. If this is not enabled for the user, no key is added.

## How to apply PPMWP custom form JS

When using a plugin which offers a custom login form, such as WooCommerce, Theme My Login etc, you can apply PPMWPs form JS using the filter/function below.


````
function example_ppm_enable_custom_form( $args ) {
     $args = array(
          'element'          => '#user_password',
          'button_class'     => '#submit_password',
          'elements_to_hide' => '#old_pw_hints',
     );
     return $args;
}

add_filter( 'ppm_enable_custom_form', 'example_ppm_enable_custom_form' );
````

Each argument in the array ('element', 'button_class' and 'elements_to_hide') can accept a single ID/class OR a comma separated list - basically any valid jQuery selector, for details see https://api.jquery.com/category/selectors/

What is each part of this array?

`element`  - This is the selector of the form element you wish to apply the PPMWP JS to
`button_class` - This is the selector for the "submit" button of your form and is used to apply "disable" which stoped the form being submitted whilst an invalid password is present.
`elements_to_hide` - This is a selector for any elements you wish to remove from the users view. This is ideal for hiding unwanted "password hints", leaving just the hints provided by our plugin in their place.

## Plugin settings

The plugin uses a fairly simply array for its settings, the standard default array the plugin comes with is as follows:

````
public $default_options = array(
  'master_switch'           => 'no',
  'enforce_password'        => 'no',
  'min_length'              => 8,
  'password_history'        => 1,
  'inherit_policies'        => 'yes',
  'password_expiry'         => array(
    'value' => 0,
    'unit'  => 'months',
  ),
  'ui_rules'                => array(
    'history'               => 'yes',
    'username'              => 'yes',
    'length'                => 'yes',
    'numeric'               => 'yes',
    'mix_case'              => 'yes',
    'special_chars'         => 'yes',
    'exclude_special_chars' => 'no',
  ),
  'rules'                   => array(
    'length'                => 'yes',
    'numeric'               => 'yes',
    'upper_case'            => 'yes',
    'lower_case'            => 'yes',
    'special_chars'         => 'yes',
    'exclude_special_chars' => 'no',
  ),
  'change_initial_password'  => 'no',
  'inactive_users_enabled'   => 'no',
  'inactive_users_expiry'    => array(
    'value' => 30,
    'unit'  => 'days',
  ),
  'inactive_users_reset_on_unlock' => 'yes',
  'failed_login_policies_enabled'  => 'no',
  'failed_login_attempts'          => 5,
  'failed_login_unlock_setting'    => 'unlock-by-admin',
  'failed_login_reset_hours'       => 1,
  'failed_login_reset_on_unblock'  => 'yes',
);
````

The important thing to to note is, as you can see above we do not store `true` or `false` values, and instead we save these settings as `yes` or `no`. To help with this the `OptionsHelper` class has the `string_to_bool` and `bool_to_string` functions, but you must ensure when adding a new option that it adheres to this formatting.

## Feature: Failed Logins

This feature (class `PPM_Failed_Logins`) allows an admin to enable roles to have limited number of failed login attempts before locking them out. Once a user has surpassed the number of allowed logins, we mark them as locked and add them to the list of inactive users.

They can try again when one of the following occurs:

 1. The user is unlocked by an admin.
 2. The last attempt (or multiple attempts) are outside of the configured window. For example if a user tries to login 23 hours ago and then again 4 times couple of minutes ago, they will be able try again in the number of hours specified in `failed_login_reset_hours` since the oldest failed attempt

### Feature settings

This feature uses the following settings.

- `failed_login_policies_enabled` - Default ('no'), this is the main switch for this feature.
- `failed_login_attempts` - Default (5), the number of attempts a user is allowed.
- `failed_login_unlock_setting` - Default ('unlock-by-admin'), can either be timed (user can unlock after X amount of time), or unlocked by admin.
- `failed_login_reset_hours` - Default (1), the number of hours if the "timed" setting is enabled.
- `failed_login_reset_on_unblock` - Default ('yes'), this sends the user a PW reset link when being unlocked.

### How it works

**Login Attempted**

When a user logs in, we run `pre_login_check` on the `authenticate` hook. In `pre_login_check` we first determine if the user's role has `failed_login_policies_enabled` enabled in the role settings, and if so, do the following:

 1. If the `failed_login_unlock_setting` is set to timed, clear any failed attempts outside of the configured time period.
 2. If the user is blocked, return back to login screen with an "account locked" error.

**Login Failure**

When a user login fails, we run `failed_login_check` on the `wp_login_failed` hook. Here we store and increment the number of users failed login attempts as detailed below:

 1. Check if the user has `failed_login_policies_enabled`
 2. Get user's current failed logins count from transient `ppmwp_user_{user_id}_failed_login_attempts`
 3. If number of failures matches or exceeds the max allowed `failed_login_attempts` apply `PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY` to usermeta and return error message to alert the user.

**Successful Login**

When a user logins in with no problem, we run `clear_failed_login_data` on the `wp_login`hook.

Here, as a user has logged in with no problem we clear any transient data held for them so they start fresh upon next login

For a breakdown of how the above "looks" in terms of logic, please refer to the flow chart below

![enter image description here](https://user-images.githubusercontent.com/59394454/108233355-9a42a480-713b-11eb-95f4-b879697ab4e2.png)

**Adding locked users to the inactive users list**

To keep things simple, when preparing the list of dormant users via the `prepare_items` function, we simply merge in any locked users by locating any users who currently have `PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY` in their usermeta.

To quickly locate all currently locked users the helper function `get_all_currently_login_locked_users` is available.

## Background Processing in PPMWP

We use WP_Background_Process for a small number of tasks within the plugin.

`PPM_User_Meta_Upgrade_Process` - This is used on plugin activation if required to move any settings/usermeta using the old prefix `_ppm_wp` to the new standard prefix `ppmwp_`

`PPM_Apply_Timestamp_For_Users_Process` - This is used on plugin activation to set the current time as a timestamp for each user on a site. This time stamp is held in the `ppmwp_last_activity` usermeta table.

`PPM_Reset_User_PW_Process` - This used inside the reset_all function and is fired when an admin resets all user's passwords. Users are sent in group of 100 to help the load on larger sites.

## How the plugin updates a users last activity.

To determine when a user was last active, we run the `update_user_last_activity` funcction on WPs `admin_init` hook. This consists of a current timestamp only and is held in the usermeta.

We also apply this `last_active` timestamp when to a new user option registration and during plugin activation for each/all users on a site.

## Validation for fields

As of v 2.5 there is a Validator class introduced.

Validator supports the following inputs:
 - Integer:
     validateInteger( $integer, int $minRange = 0, $maxRange = null )
     that method validates given integer value using the passed parameters 
     - $minRange - what is the minimum value for that integer
     - $maxRange - what is the maximum value for that integer

 - Is given value part of given set (array)
     validateInSet( $value, array $possibleValues )
     that method checks for existence of the value in the array
     - $possibleValues - array with possible values

There is ValidatorFactory class implemented, so automatic checks for values stored in array (POST, GET) can be done.
In order to use automatic validation for fields you have to provide array with certain values to check against, so it can done its work properly.

Example array that must be validated:

````
POST => [
    'min_length' => 3,
    'password_expiry' => [
        'value' => 3,
        'unit' => 'month',
    ]
]
````

It supports single and multi dimensional arrays
Example single dimension array:
````
'min_length' => [
    'typeRule' => 'number',
    'min'      => '1',
    'max'      => '10',
],
````

where:
    'min_length' - is the name of the field that must be checked
    'typeRule' - what validation rule must be applied to the value
        'number':
            possible parameters:
                'min' - minimum integer
                'max' - maximum integer
        'inset',
            possible parameters:
                'set' - array with values to check against

Example multi dimensional array:
````
'password_expiry' => [
    'value' => [
        'typeRule' => 'number',
        'min'      => '0',
    ],
    'unit' => [
        'typeRule' => 'inset',
        'set'      => [
            'months',
            'days',
            'hours',
            'seconds',
        ],
    ],
],
````

where:
    'password_expiry' - root of the array
    'value' and 'unit' - names of the fields that must be checked
    rules are the same as the above (single dimension array)


There is how the the array with validation rules must look like.

Validator has validatePasswordNotContainUsername method which is used for validating the username against given rules.
It supports 3 parameters - string $password, int $userId = 0, string $userName = ''
where:
    $password - is the password string and it is required
    $userId - the user id which password must be validated
    $userName - if the user id is not set user name must could be used
    if there is no $userId and $userName provided method tries to extract the user from the get_current_user_id()
    
the password provided is checked for containing symbols, and if it is containing userName. It returns boolean.

## Non-reviewed documentation

Any newly added documentation should be added here.

### Force password on first/next login via hook

Although our plugin offers the ability to have newly created users reset their passwords on first login, when a user is created via a custom workflow/process our plugin may not be made aware of such user’s so this setting can sometimes be “skipped”.

To avoid this, we have created a very simple filter which can be triggered via the following, using just the new users ID

    do_action( 'ppmwp_apply_forced_reset_usermeta', $new_user_id );

### Handling users with multiple policies with WPassword

Although in most cases, users within a single WordPress site each have just a single role, in some instances it may be possible for a user to have multiple roles applied to them.

Given our plugin is based on policies created for each unique role, we have added a new option which enables the plugin to tell the priority order it should use when selecting a policy to apply to a given user.

The new setting is located under Password Policies > Settings and is disabled by default  

In the above screenshot, you can see the five standard roles which are available in a default WordPress configuration - you may or may not have additional options available depending on your personal setup.

To use the option, simply drag and drop the available roles into your desired priority, placing more “important” roles (roles which should take precedence over another) at the top.

For example, let's say you have the following scenario:

 - 1 - You have users who have both the subscriber role and the editor
 - 2 - You have enabled password policies for the subscriber role    and
   for the editor role, you have select “do not enforce on this    role”

In this example, when our plugin attempts to choose a policy for this user, the wrong policy may be applied. To combat this, simply ensure the editor role is above the subscriber role in the above list.