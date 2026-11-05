/**
 * Ajax calls
 *
 * @package xfee
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */

/**
 * Handles shift form ajax call
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
document.addEventListener('submit', async function (event) {
  const form = event.target.closest('.sign_up');
  if (!form) return;

  event.preventDefault();

  // Doppelte Übermittlung verhindern
  if (form.dataset.submitting === 'true') return;

  const wrapper = form.closest('.action_form_wrap');
  if (!wrapper) return;

  const submit_btn = form.querySelector('[type="submit"]');

  form.dataset.submitting = 'true';

  if (submit_btn) {
    submit_btn.disabled = true;
  }

  // View Transition mit Fallback
  function updateWithTransition(callback) {
    if (typeof wrapper.startViewTransition === 'function') {
      wrapper.startViewTransition(callback);
    } else {
      callback();
    }
  }

  try {
    const response = await fetch(form.dataset.ajax_url, {
      method: form.method || 'POST',
      body: new FormData(form, event.submitter),
      credentials: 'same-origin',
    });

    const responseText = await response.text();
    let result;

    try {
      result = JSON.parse(responseText);
    } catch (error) {
      console.log('error', error);
      throw new Error('WordPress liefert kein gültiges JSON. Siehe Console.');
    }

    if (!response.ok || !result.success) {
      throw new Error(result.data?.message || 'Beim Absenden ist ein Fehler aufgetreten.');
    }

    updateWithTransition(() => {
      let hidden_action = form.querySelector('input[name="shift_booking_action"]');
      hidden_action.value = result.data.next_action;
      submit_btn.dataset.value = result.data.next_action;
      if (result.data.next_action === 'done') {
        wrapper.dataset.status = 'done';
        set_shift_stats(0, 1);
      } else {
        wrapper.dataset.assigned = result.data.next_action === 'cancel' ? 'booked' : 'unassigned';
        set_shift_stats(result.data.next_action === 'cancel' ? 1 : -1);
      }
    });
  } catch (error) {
    // Formular bleibt bei Fehlern erhalten
    updateWithTransition(() => {
      let feedback = form.querySelector('.form-feedback');

      if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'form-feedback';
        form.appendChild(feedback);
      }

      feedback.textContent = error.message;
    });
  }
  form.dataset.submitting = 'false';

  if (submit_btn) {
    submit_btn.disabled = false;
  }
});

/**
 * @package xfee
 * @param {number} value -
 * @returns {string}
 */
function escapeHTML(value) {
  const element = document.createElement('div');
  element.textContent = String(value ?? '');
  return element.innerHTML;
}

/**
 * Changes the shift stats without oage-load
 * @param {number} signed_delta - The difference in assigned shift count
 */
function set_shift_stats(signed_delta, done = 0) {
  const stat_open = document.querySelector('#site_stats .open');
  const stat_assigned = document.querySelector('#site_stats .assigned');
  let value_open = parseInt(stat_open.getAttribute('value'));
  let value_assigned = parseInt(stat_assigned.getAttribute('value'));
  stat_open.setAttribute('value', value_open - signed_delta);
  stat_assigned.setAttribute('value', value_assigned + signed_delta - done);
}
