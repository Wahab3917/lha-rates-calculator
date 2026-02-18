'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const continueBtn = document.getElementById('lha-continueBtn');
  const getRateBtn = document.getElementById('lha-getRateBtn');
  const rateEl = document.getElementById('lha-rate');
  const step1 = document.getElementById('lha-step-1');
  const step2 = document.getElementById('lha-step-2');

  // Store user details
  let userName = '';
  let userEmail = '';

  if (!continueBtn || !getRateBtn || !rateEl || !step1 || !step2) return;

  const hideMessage = () => {
    rateEl.textContent = '';
    rateEl.classList.add('lha-hidden');
    rateEl.classList.remove('lha-rate--error', 'lha-rate--success', 'lha-rate--info');
  };

  const showMessage = (text, type = 'error') => {
    rateEl.textContent = String(text || '');
    rateEl.classList.remove('lha-hidden');
    rateEl.classList.remove('lha-rate--error', 'lha-rate--success', 'lha-rate--info');
    rateEl.classList.add(`lha-rate--${type}`);
  };

  // Step 1: Continue button - validate name and email
  continueBtn.addEventListener('click', () => {
    const name = document.getElementById('lha-name').value.trim();
    const email = document.getElementById('lha-email').value.trim();

    if (!name || !email) {
      showMessage('Please enter both your name and email.', 'error');
      return;
    }

    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showMessage('Please enter a valid email address.', 'error');
      return;
    }

    // Store user details
    userName = name;
    userEmail = email;
    hideMessage();

    // Show step 2
    step1.classList.add('lha-hidden');
    step2.classList.remove('lha-hidden');
  });

  // Step 2: Get LHA Rate button
  getRateBtn.addEventListener('click', async () => {
    const postcode = document.getElementById('lha-postcode').value.trim();
    const bedrooms = document.getElementById('lha-bedrooms').value;

    if (!postcode || !bedrooms) {
      showMessage('Please enter both postcode and number of bedrooms.', 'error');
      return;
    }

    showMessage('Loading…', 'info');

    try {
      const form = new FormData();
      form.append('action', 'lha_rates_fetch');
      form.append('nonce', LHA_Rates.nonce);
      form.append('name', userName);
      form.append('email', userEmail);
      form.append('postcode', postcode);
      form.append('bedrooms', bedrooms);

      const res = await fetch(LHA_Rates.ajax_url, {
        method: 'POST',
        body: form,
      });

      if (!res.ok) {
        throw new Error(`Network error (${res.status})`);
      }

      const json = await res.json();

      if (!json.success) {
        throw new Error(json.data || 'Unknown API error');
      }

      // json.data contains the API response returned from server (PropertyData JSON)
      const api = json.data || {};
      let output = '';

      if (api.data && (api.data.brma || api.data.rate)) {
        output = `${api.data.brma || ''}  |  £${api.data.rate || ''}`;
      } else if (api.brma || api.rate) {
        // fallback if API format slightly different
        output = `${api.brma || ''}  |  £${api.rate || ''}`;
      } else {
        // fallback dump
        output = JSON.stringify(api);
      }

      showMessage(output, 'success');
    } catch (err) {
      showMessage(err?.message || 'Something went wrong.', 'error');
      // console.error(err);
    }
  });
});
