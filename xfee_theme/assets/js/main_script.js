/**
 * Frontend scripts: Tabs, countdown
 *
 * @package xfee
 * @author Richard Lemke <richard@mrhide.de>
 */

/**
 * Handling tabs, e.g. shift details
 *
 * @since 0.9.0
 */
const all_tabs = document.querySelectorAll('button[data-detail-id]');
// Adds event listener
for (let i = 0; i < all_tabs.length; i++) {
  all_tabs[i].addEventListener('click', toggle_tab);
}
// Handles tab open and switch
function toggle_tab(e) {
  const button = e.target;
  const detail_id = button.getAttribute('data-detail-id');
  const target_tab = document.querySelector('div[data-detail-id="' + detail_id + '"]');

  let content_wrap = target_tab.querySelector('div');
  if (target_tab.classList.contains('open')) {
    // Close
    target_tab.classList.remove('open');
    button.classList.remove('open');
    content_wrap.style = `max-height: 0`;
  } else {
    // Open
    document.querySelectorAll('.open').forEach((tab) => {
      tab.classList.remove('open');
      if (tab.querySelector('div') !== null) tab.querySelector('div').style = '';
    });
    content_wrap.style = `max-height: ${content_wrap.scrollHeight}px`;
    button.classList.add('open');
    target_tab.classList.add('open');
  }
}

/**
 * Handles forms
 *
 * @since 0.9.2
 */
function set_shift_form(form = false) {
  // per element or all
  let forms;
  if (form !== false) {
    // one form
    forms = [form];
  } else {
    forms = document.getElementsByClassName('action_form_wrap');
  }
  for (i = 0; i < forms.length; i++) {
    let assigned = forms[i].dataset.assigned;
    let status = forms[i].dataset.status;
    if (assigned === 'booked') {
      if (status === 'pre_process' || status === 'queue_process' || status === 'begin_process') {
        set_form_field(forms[i], 'form_cancel');
      } else if (status === 'done') {
        set_form_field(forms[i], 'form_done');
      } else if (status === 'end_process' || status === 'post_process') {
        set_form_field(forms[i], 'form_finish');
      }
    } else if (assigned === 'assigned') {
      if (status === 'done') {
        set_form_field(forms[i], 'form_done');
      } else {
        set_form_field(forms[i], 'form_assigned');
      }
    } else if (assigned === 'unassigned') {
      if (status === 'pre_process') {
        set_form_field(forms[i], 'form_sign');
      } else if (status === 'queue_process' || status === 'begin_process') {
        set_form_field(forms[i], 'form_sign_urgent');
      } else {
        set_form_field(forms[i], 'form_failed');
      }
    }
  }
}

/**
 * Handles form fields
 *
 * @since 0.9.2
 */
function set_form_field(form, field) {
  console.log('field', field);
  const fields = form.querySelectorAll('[data-condition]');
  for (j = 0; j < fields.length; j++) {
    fields[j].disabled = true;
  }
  const new_fields = form.querySelectorAll('[data-condition="' + field + '"]');
  for (j = 0; j < new_fields.length; j++) {
    new_fields[j].disabled = false;
  }
}

set_shift_form();

/**
 * Updates all countdowns on shift page
 *
 * @since 0.9.0
 */
function updateCountdowns() {
  document.querySelectorAll('.countdown').forEach(function (countdown) {
    // Convert the target Unix timestamp from seconds to milliseconds.
    const target = Number(countdown.dataset.target) * 1000;
    const end = Number(countdown.dataset.end) * 1000;
    let text = 'starts in ';

    // Calculate the remaining time in seconds.
    let remaining = Math.floor((target - Date.now()) / 1000);

    // Shif started
    if (remaining <= 0) {
      remaining = Math.floor((end - Date.now()) / 1000);
      text = 'ends in ';
    }
    if (remaining <= 0) {
      remove_countdown(countdown);
      return;
    }

    // Calculate the remaining days, hours, minutes, and seconds.
    const days = Math.floor(remaining / 86400);
    remaining %= 86400;
    const hours = Math.floor(remaining / 3600);
    remaining %= 3600;
    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;

    // Build the countdown text with correct singular and plural forms.
    const parts = [];
    if (days > 0) parts.push(days + 'd');
    if (hours > 0) parts.push(hours + 'h');
    if (minutes > 0) parts.push(minutes + 'min');
    if (minutes < 5 && seconds > 0) parts.push(seconds + 's');

    // Update the countdown with the formatted countdown.
    if (parts.length != 0) countdown.textContent = text + parts.join(' ');
  });
}

// Init countdown
updateCountdowns();
setInterval(updateCountdowns, 1000);

/**
 * Updates form after countdown has finished
 *
 * @since 0.9.2
 */
function remove_countdown(countdown) {
  countdown.parentElement.dataset.status = 'done';
  set_shift_form(countdown.parentElement);
  countdown.remove();
}
