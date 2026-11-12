// @package xfee

const wp_post_id = jQuery('#post_ID').val();

// Add Goodie option row
jQuery('#add_goodie_option').click(function () {
  let name = jQuery('#add_goodie_option_name').val();
  if (name == '') return;
  let row_html = '<label for="' + name + '">' + name + '</label><input type="number" id="' + name + '" name="goodie_fields[options][' + name + ']" value="0">';
  row_html += '<button type="button" data-goodie="' + name + '" onclick="remove_goodie_option(this)">-</button>';
  jQuery('#add_goodie_option_name').before(row_html);
  jQuery('#add_goodie_option_name').val('');
  jQuery('#add_goodie_option_name').focus();
});

// Remove Goodie option
function remove_goodie_option(e) {
  const html_id = e.getAttribute('data-goodie');
  const count = jQuery('#' + html_id).val();
  jQuery('#' + html_id).remove();
  jQuery('label[for=' + html_id + ']').remove();
  jQuery(e).remove();
}

// Add Shift option row
jQuery('#add_new_shift').click(add_new_shift);

function add_new_shift(e, date) {
  if (typeof date === 'undefined') {
    date = jQuery('#add_new_shift_date').val(); // Get it from form
  }
  if (date == '') return;

  const signature = date + '-' + wp_post_id + '-' + Math.floor(Math.random() * 10000);
  const weekday = get_weekday(date);
  // const next_key = parseInt(jQuery('#add_new_shift').attr('data-date')) + 1;
  const row_html = get_occurrence_row_template(date, weekday, signature);
  jQuery('#shift_rhythms').before(row_html);
  jQuery('#add_new_shift').attr('data-date', date);
  jQuery('#add_new_shift_date').val(0);
}

// Remove Shift option
function remove_shift_occurrence(e) {
  const date = e.getAttribute('data-date');
  const assignee = jQuery('select[data-date="' + date + '"]').val();
  if (assignee != '' && assignee != 'unassigned' && assignee != 0) {
    if (confirm('Delete assigned shift?') != true) return;
  }
  jQuery('[data-date="' + date + '"]').remove();
}

// Add Shifts automatically

jQuery('#add_new_shift_day').click(function () {
  let date = jQuery('#add_new_shift').attr('data-last_date');
  if (date == '') return;
  date = add_days(date, 1);
  add_new_shift(false, date);
});

jQuery('#add_new_shift_week').click(function () {
  let date = jQuery('#add_new_shift').attr('data-last_date');
  if (date == '') return;
  date = add_days(date, 7);
  add_new_shift(false, date);
});

jQuery('#add_new_shift_month').click(function () {
  let date = jQuery('#add_new_shift').attr('data-last_date');
  if (date == '') return;
  date = next_monthly_event(date);
  if (date == null) {
    alert("Date doesn't exist in that month!");
  } else {
    add_new_shift(false, date);
  }
});

/**
 *
 * @param {string} date_input  - Current date.
 * @returns {number} Sum of a and b.
 */
function get_weekday(date) {
  const [year, month, day] = date.split('-').map(Number);

  const d = new Date(Date.UTC(year, month - 1, day));

  return new Intl.DateTimeFormat('en-GB', {
    weekday: 'long',
    timeZone: 'UTC',
  }).format(d);
}

function add_days(date, days) {
  const [year, month, day] = date.split('-').map(Number);
  const d = new Date(Date.UTC(year, month - 1, day));

  d.setUTCDate(d.getUTCDate() + days);

  return d.toISOString().slice(0, 10);
}

function add_hours(date, hours) {
  const d = new Date(date);

  d.setUTCHours(d.getUTCHours() + hours);

  return d.toISOString().slice(0, 19).replace('T', ' ');
}

/**
 * Calculates monthly patterns of occurrences.
 * @param {string} date_input  - Current date.
 * @returns {number} Sum of a and b.
 */
function get_monthly_pattern(date_input) {
  const [year, month, day] = date_input.split('-').map(Number);
  const date = new Date(Date.UTC(year, month - 1, day));
  const weekday = date.getUTCDay();
  const occurrence = Math.ceil(day / 7);
  return {
    weekday,
    occurrence,
    description: `${occurrence}. ${new Intl.DateTimeFormat('de-DE', {
      weekday: 'long',
      timeZone: 'UTC',
    }).format(date)} im Monat`,
  };
}

/**
 * Calculates the next occurrence in 1 month.
 * @param {string} date_input - Current date.
 * @returns {number} Sum of a and b.
 */
function next_monthly_event(date_input) {
  const [year, month, day] = date_input.split('-').map(Number);

  const date = new Date(Date.UTC(year, month - 1, day));
  const weekday = date.getUTCDay();
  const occurrence = Math.ceil(day / 7);

  let nextMonth = month + 1;
  let nextYear = year;

  if (nextMonth > 12) {
    nextMonth = 1;
    nextYear++;
  }

  const firstDay = new Date(Date.UTC(nextYear, nextMonth - 1, 1));
  const offset = (weekday - firstDay.getUTCDay() + 7) % 7;
  const nextDay = 1 + offset + (occurrence - 1) * 7;

  // Falls es den Termin im Folgemonat nicht gibt
  const lastDay = new Date(Date.UTC(nextYear, nextMonth, 0)).getUTCDate();

  if (nextDay > lastDay) {
    return null;
  }

  return `${nextYear}-${String(nextMonth).padStart(2, '0')}-${String(nextDay).padStart(2, '0')}`;
}

/**
 * Calculates the next occurrence in 1 month.
 * @param {string} date - The first number.
 * @param {string} weekday - Weekday written out.
 * @param {string} signature - Unique occurrence id
 * @returns {string} Filled out shift row template
 */
function get_occurrence_row_template(date, weekday, signature) {
  const occurrence_template = jQuery('#occurrence_row_template').html();
  const replace_table = {
    date: date,
    signature: signature,
    weekday: weekday,
    done: 'false',
    blocker: 'name=',
  };

  return occurrence_template.replace(/\{(\w+)\}/g, (match, key) => replace_table[key] ?? match);
}

// Switch attribute on change on user select list
jQuery('select[data-assigned]').on('change', function (e) {
  if (e.target.value != 0) {
    e.target.setAttribute('data-assigned', 1);
  } else {
    e.target.setAttribute('data-assigned', 0);
  }
});

function toggle_admin_user_details(btn, user_html_id) {
  console.log('btn', btn);
  const button = jQuery(btn);
  const user_details = jQuery('#' + user_html_id);
  if (button.val() === 'show') {
    jQuery('[data-visible="1"]').attr('data-visible', 0);
    jQuery('#admin_user_list_table button[value="close"]').val('show');
    button.val('close');
    user_details.attr('data-visible', 1);
  } else {
    button.val('show');
    user_details.attr('data-visible', 0);
  }
}

function close_admin_user_details(btn, user_html_id) {
  const user_details = jQuery('#' + user_html_id);
  if (user_details.attr('data-visible') === '1') {
    user_details.attr('data-visible', 0);
  }
}
