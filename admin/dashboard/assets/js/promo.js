(function(){
  const PROMO_URL = 'https://api.plink.ly/promo.php';

  fetch(PROMO_URL)
    .then(res => res.json())
    .then(data => {
      if (!data.enabled || !data.id) return;

     // Fetch the latest dismissed id by the user
      const dismissedId = localStorage.getItem('plinkly_promo_dismissed_id');
      if (dismissedId === data.id) return;

      // Create the popup element
      const popup = document.createElement('div');
      popup.id = 'plinkly-popup';
      popup.innerHTML = `
        <div class="plinkly-popup-inner">
          <button class="plinkly-popup-close" title="Close">&times;</button>
          <h2>${data.title || ''}</h2>
          <p>${data.message || ''}</p>
          ${data.cta && data.cta.url ? `<a href="${data.cta.url}" class="plinkly-popup-cta" target="_blank">${data.cta.label || 'Learn More'}</a>` : ''}
        </div>
      `;
      document.body.appendChild(popup);

      // When closing the popup: save only the id of this notification
      popup.querySelector('.plinkly-popup-close').onclick = function() {
        popup.remove();
        localStorage.setItem('plinkly_promo_dismissed_id', data.id);
      };
    });
})();
