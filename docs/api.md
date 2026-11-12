# API Reference

This document describes the available functions and their return structures.

## Table of Contents

- [User roles](#user-roles)
- [get_all_users()](#get_all_users)

- [get_all_shifts()](#get_all_shifts)
- [get_user_shifts($user_id)](#get_user_shiftsuser_id)
- [get_shift_status($args)](#get_shift_statusargs)
- [get_shift_fields($shift_id)](#get_shift_fieldsshift_id--internal)

- [get_all_goodies()](#get_all_goodies)
- [get_goodie_fields($goodie_id)](#get_goodie_fieldsgoodie_id--internal)

- [get_user_pick_ups($user_id)](#get_user_pick_upsuser_id)
- [get_pick_up_trash()](#get_pick_up_trash)

---

## User roles

| Role            | Description                                    |
| --------------- | ---------------------------------------------- |
| `author`        | Logged-in user                                 |
| `editor`        | Administrator for shifts, goodies and pick-ups |
| `administrator` | WordPress administrator                        |

## get_all_users()

Returns a collection of users and their associated pick-up information.

### Return Structure

The returned array contains multiple users. Each entry follows this structure:

| Key             | Type    | Default Value | Description                         |
| --------------- | ------- | ------------- | ----------------------------------- |
| `id`            | Integer | `0`           | Unique identifier of the user       |
| `name`          | String  | `''`          | Name of the user                    |
| `user_email`    | String  | `''`          | Email address of the user           |
| `signed_count`  | Integer | `0`           | Number of signed shifts             |
| `acorns`        | Array   | `[]`          | Collection of user acorn signatures |
| `pick_up_count` | Integer | `0`           | Number of pick-ups                  |
| `pick_ups`      | Array   | `[]`          | Collection of the user's pick-ups   |
| ↳ `user_id`     | Integer | `0`           | ID of the associated user           |
| ↳ `option`      | String  | `''`          | Selected option                     |
| ↳ `acorns`      | Array   | `[]`          | Collection of reserved user acorns  |

---

## get_all_shifts()

Returns a collection of all available shifts.

### Return Structure

Each shift contains the following fields:

| Key            | Type    | Default Value | Description                                      |
| -------------- | ------- | ------------- | ------------------------------------------------ |
| `id`           | Integer | `0`           | Unique identifier of the shift                   |
| `name`         | String  | `''`          | Title of the shift                               |
| `date`         | String  | `''`          | Date of the shift                                |
| `time_from`    | String  | `''`          | Shift start time                                 |
| `time_to`      | String  | `''`          | Shift end time                                   |
| `details_html` | String  | `''`          | HTML-formatted shift details                     |
| `compensation` | Integer | `0`           | Compensation amount                              |
| `signature`    | String  | `''`          | Acorn signature associated with the shift        |
| `weekday`      | String  | `''`          | Weekday of the shift                             |
| `assign_id`    | Integer | `0`           | Associated assigned user ID, 0 equals unassigned |
| `done`         | Boolean | `false`       | Indicates whether the shift has been completed   |

## get_user_shifts($user_id)

Returns a collection of all shifts associated with a specific user.

### Parameters

| Parameter  | Type    | Description    |
| ---------- | ------- | -------------- |
| `$user_id` | Integer | ID of the user |

### Return Structure

Each shift contains the following fields:

| Key            | Type    | Default Value   | Description                                         |
| -------------- | ------- | --------------- | --------------------------------------------------- |
| `shift_id`     | Integer | `0`             | Unique identifier of the shift                      |
| `name`         | String  | `''`            | Title of the shift                                  |
| `date`         | String  | `''`            | Date of the shift                                   |
| `details_html` | String  | `''`            | HTML-formatted shift details                        |
| `time_from`    | String  | `''`            | Shift start time                                    |
| `time_to`      | String  | `''`            | Shift end time                                      |
| `compensation` | Integer | `0`             | Compensation amount                                 |
| `signature`    | String  | `''`            | Acorn signature associated with the shift           |
| `weekday`      | String  | `''`            | Weekday of the shift                                |
| `assign_id`    | Integer | `0`             | Associated assignment ID                            |
| `done`         | Boolean | `false`         | Indicates whether the shift has been completed      |
| `assigned`     | Boolean | `false`         | Indicates whether the shift is assigned to the user |
| `status`       | String  | `'pre_process'` | [Status](#get_shift_statusargs) of the shift        |

## get_shift_status($args)

Determines the temporal status and assignment state of a shift.

### Parameters

| Parameter     | Type    | Default Value | Description                                    |
| ------------- | ------- | ------------- | ---------------------------------------------- |
| `$args`       | Array   | `[]`          | Arguments used to determine the shift status   |
| ↳ `date`      | String  | `''`          | Date of the shift                              |
| ↳ `assign_id` | Integer | `0`           | ID of the associated assignment                |
| ↳ `time_from` | String  | `''`          | Shift start time                               |
| ↳ `time_to`   | String  | `''`          | Shift end time                                 |
| ↳ `done`      | Boolean | `false`       | Indicates whether the shift has been completed |

### Return Value

Returns an indexed array containing two values:

| Index | Variable    | Type   | Description           |
| ----- | ----------- | ------ | --------------------- |
| `0`   | `$assigned` | String | Assignment status     |
| `1`   | `$status`   | String | Temporal shift status |

### Shift Status Flag

The status flag reflects the temporal phase of a shift. It does not indicate whether the shift is assigned. This is handled separately by the assigned flag.

| Status          | Description                                                   |
| --------------- | ------------------------------------------------------------- |
| `pre_process`   | Shift is ready for **sign-up**                                |
| `queue_process` | Shift is still ready for **sign-up** on the same day (urgent) |
| `begin_process` | Shift has started but can still be **cancelled**              |
| `end_process`   | Shift is currently running and can be **finished**            |
| `post_process`  | Shift has ended and needs to be **finished** (acorn transfer) |
| `done`          | Shift is finished and ready for deletion                      |

### Shift Assigned Flag

The assigned flag only reflects the assignment state of a shift. Whether the shift is in the future or past is determined by the status flag.

| Status       | Description                           |
| ------------ | ------------------------------------- |
| `unassigned` | Shift is not assigned                 |
| `assigned`   | Shift is assigned to a user           |
| `booked`     | Shift is assigned to the current user |

## get_shift_fields($shift_id) `[Internal]`

Returns the internal data structure of a shift.

### Parameters

| Parameter   | Type    | Description     |
| ----------- | ------- | --------------- |
| `$shift_id` | Integer | ID of the shift |

### Return Structure

| Key                | Type    | Default Value | Description                                    |
| ------------------ | ------- | ------------- | ---------------------------------------------- |
| `id`               | Integer | `0`           | Unique identifier of the shift                 |
| `name`             | String  | `''`          | Title of the shift                             |
| `details_markdown` | String  | `''`          | Shift details in Markdown format               |
| `details_html`     | String  | `''`          | HTML-formatted shift details                   |
| `time_from`        | String  | `''`          | Shift start time                               |
| `time_to`          | String  | `''`          | Shift end time                                 |
| `compensation`     | Integer | `0`           | Compensation amount                            |
| `signature`        | String  | `''`          | Acorn signature associated with the shift      |
| `dates`            | Array   | `[]`          | Occurences of shift                            |
| ↳ `date`           | String  | `''`          | Date of the shift                              |
| ↳ `assign_id`      | Integer | `0`           | Assigned user ID, 0 equals not unassigned      |
| ↳ `time_from`      | String  | `''`          | Start time for this date                       |
| ↳ `time_to`        | String  | `''`          | End time for this date                         |
| ↳ `signature`      | String  | `''`          | Acorn signature associated with the shift      |
| ↳ `done`           | Boolean | `false`       | Indicates whether the shift has been completed |

---

## get_all_goodies()

Returns a collection of goodies formatted for frontend output.

### Return Structure

The returned array contains multiple goodies. Each entry follows this structure:

| Key             | Type    | Default Value | Description                                       |
| --------------- | ------- | ------------- | ------------------------------------------------- |
| `id`            | Integer | `0`           | Unique identifier of the goodie                   |
| `name`          | String  | `''`          | Title of the goodie                               |
| `in_stock`      | Integer | `0`           | Available stock quantity                          |
| `details`       | String  | `''`          | HTML-formatted goodie details                     |
| `image`         | String  | `''`          | HTML markup of the goodie image                   |
| `compensation`  | Integer | `0`           | Compensation amount                               |
| `options`       | Object  | `{}`          | Available options and their stock quantities      |
| ↳ `XL`          | Integer | `0`           | Available quantity of an option                   |
| `pick_ups`      | Array   | `[]`          | Collection of reserved goodies for pick-up        |
| ↳ `user_id`     | Integer | `0`           | ID of the assigned user                           |
| ↳ `goodie_name` | String  | `''`          | Title of the goodie                               |
| ↳ `option`      | String  | `''`          | Selected option                                   |
| ↳ `reserve`     | Integer | `0`           | Acorn quantity, the signatures stay with the user |

## get_goodie_fields($goodie_id) `[Internal]`

Returns a single record representing the internal goodie data structure.

### Parameters

| Parameter    | Type    | Description      |
| ------------ | ------- | ---------------- |
| `$goodie_id` | Integer | ID of the goodie |

### Return Structure

| Key                | Type    | Default Value | Description                                             |
| ------------------ | ------- | ------------- | ------------------------------------------------------- |
| `name`             | String  | `''`          | Title of the goodie                                     |
| `in_stock`         | Integer | `0`           | Available stock quantity                                |
| `details_markdown` | String  | `''`          | Goodie details in Markdown format                       |
| `details_html`     | String  | `''`          | HTML-formatted goodie details                           |
| `image_html`       | String  | `''`          | HTML markup of the goodie image                         |
| `image_url`        | String  | `''`          | URL of the goodie image                                 |
| `compensation`     | Integer | `0`           | Compensation amount                                     |
| `options`          | Object  | `{}`          | Available options and their stock quantities            |
| ↳ `XL`             | Integer | `0`           | Available quantity of an option                         |
| `pick_ups`         | Array   | `[]`          | Collection of reserved goodies for pick-up              |
| ↳ `user_id`        | Integer | `0`           | ID of the assigned user                                 |
| ↳ `goodie_name`    | String  | `''`          | Title of the goodie                                     |
| ↳ `option`         | String  | `''`          | Selected option                                         |
| ↳ `reserve`        | Integer | `0`           | Acorn quantity, the acorn signatures stay with the user |

---

## get_user_pick_ups($user_id)

Returns a collection of pick-ups associated with a specific user.

### Parameters

| Parameter  | Type    | Description    |
| ---------- | ------- | -------------- |
| `$user_id` | Integer | ID of the user |

### Return Structure

Returns a collection of pick-up objects.

| Key           | Type    | Default Value | Description                                                            |
| ------------- | ------- | ------------- | ---------------------------------------------------------------------- |
| `goodie_id`   | Integer | `0`           | Unique identifier of the goodie                                        |
| `goodie_name` | String  | `''`          | Title of the goodie                                                    |
| `option`      | String  | `''`          | Selected option, e.g. XL                                               |
| `acorns`      | Array   | `[]`          | Collection of reserved user acorns, separately stored till **pick-up** |
| `code`        | Integer | `0`           | Pick-up code                                                           |

## get_pick_up_trash()

Returns the pick-up trash data grouped by user ID.

### Return Structure

Each user entry contains the following fields:

| Key         | Type    | Default Value | Description                 |
| ----------- | ------- | ------------- | --------------------------- |
| `user_id`   | Integer | `0`           | ID of the current user      |
| `goodie_id` | Integer | `0`           | ID of the associated goodie |
| `option`    | String  | `''`          | Selected option             |
| `code`      | Integer | `0`           | Pick-up code                |
