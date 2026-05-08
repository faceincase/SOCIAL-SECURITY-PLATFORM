<footer class="mt-8 pt-6 border-t border-gray-300">
  <div class="flex items-center justify-center gap-6 text-sm text-gray-600">
    <a href="#" class="hover:text-gray-900 transition-colors" data-popup="contact">CONTACT US</a>
    <span class="text-gray-400">•</span>
    <a href="#" class="hover:text-gray-900 transition-colors" data-popup="privacy">PRIVACY POLICY</a>
    <span class="text-gray-400">•</span>
    <a href="#" class="hover:text-gray-900 transition-colors" data-popup="terms">TERMS OF SERVICE</a>
    <span aria-hidden="true" class="inline-block w-px h-4 bg-gray-300"></span>
    <a href="#" class="hover:text-gray-900 transition-colors" data-popup="accreditation">ACCREDITATION</a>
  </div>
</footer>

<div id="footerPopup" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg border border-gray-200 w-full max-w-2xl mx-4">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
      <h3 id="footerPopupTitle" class="text-base font-semibold text-gray-900"></h3>
      <button id="footerPopupClose" class="text-gray-500 hover:text-gray-700">
        ✕
      </button>
    </div>
    <div id="footerPopupBody" class="px-5 py-4 text-sm text-gray-600"></div>
  </div>
</div>

<script>
  (function () {
    const popup = document.getElementById('footerPopup');
    const titleEl = document.getElementById('footerPopupTitle');
    const bodyEl = document.getElementById('footerPopupBody');
    const closeBtn = document.getElementById('footerPopupClose');

    const contentMap = {
      contact: `
        <div class="space-y-3">
          <p class="text-sm text-gray-700">For enquiries, feedback, support, or data requests, please contact us.<br>We aim to respond within <span class="font-semibold">1 month</span> for data access/deletion requests.</p>
          <div class="text-sm text-gray-700">
            <div><span class="font-semibold">Email:</span> <a class="text-sky-700 hover:text-sky-900" href="mailto:contact@socialinputplatform.co.uk">contact@socialinputplatform.co.uk</a></div>
            <div><span class="font-semibold">Phone:</span> <a class="text-sky-700 hover:text-sky-900" href="tel:+44000000000">+44 000000 0000</a></div>
          </div>
        </div>
      `,
      privacy: `
        <div class="space-y-4 text-gray-700">
          <p>We are committed to protecting your personal data in line with UK law.</p>

          <div>
            <h4 class="font-semibold text-gray-900 mb-1">Data Protection (UK GDPR and Data Protection Act 2018)</h4>
            <p>Under UK GDPR and the Data Protection Act 2018, we must process personal data lawfully, fairly, and transparently. Data must be collected for clear purposes, be relevant, and limited to what is necessary.</p>
            <p class="mt-2">Depending on the service, protected and personal data may include details such as name, age, ID information, location data, account security data, and other sensitive characteristics where legally required and handled with extra care.</p>
          </div>

          <div>
            <h4 class="font-semibold text-gray-900 mb-1">Your Rights</h4>
            <p>You can request a copy of your personal data and request deletion of your data. We will respond within <span class="font-semibold">one month</span>, as required by UK GDPR.</p>
          </div>

          <div>
            <h4 class="font-semibold text-gray-900 mb-1">Cookie and Electronic Communications (PECR)</h4>
            <p>We only use necessary cookies by default. You can refuse non-essential cookies. We follow UK PECR requirements, including enforcement standards where non-compliance may result in significant penalties.</p>
          </div>

          <div>
            <h4 class="font-semibold text-gray-900 mb-1">How We Protect Data</h4>
            <p>Data is stored securely in UK-based systems. We apply baseline encryption for data in transit and at rest, and access is controlled by our security processes. If a qualifying data breach occurs, affected users and regulators are informed in line with legal timelines, including the 72-hour reporting requirement where applicable.</p>
          </div>

          <p class="text-sm">To request data access or deletion, contact <a class="text-sky-700 hover:text-sky-900" href="mailto:contact@socialinputplatform.co.uk">contact@socialinputplatform.co.uk</a> or call <a class="text-sky-700 hover:text-sky-900" href="tel:+44000000000">+44 000000 0000</a>.</p>
        </div>
      `,
accreditation: `
  <div class="space-y-5 text-gray-700">
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
      <p class="text-sm opacity-90">
        Edinburgh Napier University • SOC09109 2025–6 TR2 001
      </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
      <p class="text-sm leading-relaxed">
        This project was completed as a collaborative university assignment over a 
        <span class="font-semibold text-gray-900">12-week development period</span>, 
        bringing together multiple disciplines including design, development and cybersecurity.
      </p>
    </div>

    <div>
      <h4 class="font-semibold text-gray-900 mb-3 text-base">Team Members</h4>
      <div class="grid gap-2 text-sm">
        
        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
          <span><span class="font-medium">Alexander Gordon</span> <span class="text-gray-500" style="opacity: 0.7;">(40690935)</span></span>
          <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">Project Manager</span>
        </div>

        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
          <span><span class="font-medium">Bikiza Otiende</span> <span class="text-gray-500" style="opacity: 0.7;">(40690993)</span></span>
          <span class="text-xs bg-pink-100 text-pink-700 px-2 py-1 rounded-full">UI/UX</span>
        </div>

        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
          <span><span class="font-medium">Johnathan Brown</span> <span class="text-gray-500" style="opacity: 0.7;">(40690917)</span></span>
          <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Cyber Team</span>
        </div>

        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
          <span><span class="font-medium">Lucia Rufo</span> <span class="text-gray-500" style="opacity: 0.7;">(40723530)</span></span>
          <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Cyber Team</span>
        </div>

        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
          <span><span class="font-medium">Miko</span> <span class="text-gray-500" style="opacity: 0.7;">(40690931)</span></span>
          <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Web Development</span>
        </div>

        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
          <span><span class="font-medium">Bobby Barty</span> <span class="text-gray-500" style="opacity: 0.7;">(40780019)</span></span>
          <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Legal / Web Development Support</span>
        </div>

      </div>
    </div>

    <p class="text-xs text-gray-400 text-center">
      Student identifiers are shown instead of full email addresses for privacy.
    </p>

  </div>
`,
      terms: `
        <div class="space-y-4 text-gray-700">
          <p>By using this service, you agree to the following Terms of Service and Code of Conduct.</p>

          <div>
            <h4 class="font-semibold text-gray-900 mb-1">Our Data Use</h4>
            <ul class="list-disc pl-5 space-y-1">
              <li>We store personal data needed to operate your account and issue reporting features.</li>
              <li>Data may include: username, hashed password, issue postcode, street location, and uploaded images used as supporting evidence.</li>
              <li>Data is retained until deletion is requested, unless a longer retention period is required by law.</li>
            </ul>
          </div>

          <div>
            <h4 class="font-semibold text-gray-900 mb-1">Code of Conduct</h4>
            <ol class="list-decimal pl-5 space-y-1">
              <li>We may store and process submitted data and images when you accept these terms.</li>
              <li>We may suspend or terminate posts and accounts that breach these terms, without prior notice.</li>
              <li>You are responsible for keeping account details secure. We are not liable for unauthorised access caused by weak passwords or shared logins.</li>
              <li>We are not responsible for data loss caused by downtime or technical issues outside reasonable control.</li>
              <li>We may update these terms at any time. Continued use of the service means you accept the updated terms.</li>
              <li>You must not post illegal, defamatory, or knowingly false reports. Violations may lead to content removal, account strikes, or permanent bans.</li>
            </ol>
          </div>

          <p class="text-sm">For data processing, access, or deletion requests, contact <a class="text-sky-700 hover:text-sky-900" href="mailto:contact@socialinputplatform.co.uk">contact@socialinputplatform.co.uk</a> or <a class="text-sky-700 hover:text-sky-900" href="tel:+44000000000">+44 000000 0000</a>. We respond within one month.</p>
        </div>
      `
    };

    function openPopup(title, key) {
      titleEl.textContent = title;
      bodyEl.innerHTML = contentMap[key] || '';
      popup.classList.remove('hidden');
      popup.classList.add('flex');
    }

    function closePopup() {
      popup.classList.add('hidden');
      popup.classList.remove('flex');
    }

    document.querySelectorAll('[data-popup]').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        openPopup(link.textContent.trim(), link.dataset.popup);
      });
    });

    closeBtn.addEventListener('click', closePopup);
    popup.addEventListener('click', (e) => {
      if (e.target === popup) closePopup();
    });
  })();
</script>
