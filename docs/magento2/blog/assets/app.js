// Simple TOC generation and back-to-top button
(function() {
  // Back to top button
  window.addEventListener('scroll', function() {
    var btn = document.getElementById('backToTopBtn');
    if (!btn) return;
    if (window.scrollY > 300) {
      btn.classList.add('visible');
    } else {
      btn.classList.remove('visible');
    }
  });

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      var target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // Highlight TOC links on scroll
  window.addEventListener('scroll', function() {
    var tocLinks = document.querySelectorAll('.toc-nav a');
    if (!tocLinks.length) return;

    tocLinks.forEach(function(link) {
      var targetId = link.getAttribute('href').substring(1);
      var target = document.getElementById(targetId);
      if (!target) return;

      var rect = target.getBoundingClientRect();
      var inView = rect.top <= 200 && rect.bottom >= 100;

      if (inView) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  });
})();
