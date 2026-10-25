// ===== MOBILE NAV =====
const hamburger = document.querySelector('.hamburger');
const navLinks = document.querySelector('.nav-links');

hamburger.addEventListener('click', () => {
  hamburger.classList.toggle('active');
  navLinks.classList.toggle('open');
});

// Close nav on link click
document.querySelectorAll('.nav-links a').forEach(link => {
  link.addEventListener('click', () => {
    hamburger.classList.remove('active');
    navLinks.classList.remove('open');
  });
});

// ===== HEADER SHADOW ON SCROLL =====
const header = document.querySelector('header');
window.addEventListener('scroll', () => {
  if (window.scrollY > 50) {
    header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.12)';
  } else {
    header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.08)';
  }
});

// ===== SCROLL TO TOP =====
const scrollBtn = document.querySelector('.scroll-top');
window.addEventListener('scroll', () => {
  if (window.scrollY > 400) {
    scrollBtn.classList.add('visible');
  } else {
    scrollBtn.classList.remove('visible');
  }
});
scrollBtn.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

// ===== FADE IN ON SCROLL =====
const fadeEls = document.querySelectorAll('.fade-in');
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => {
        entry.target.classList.add('visible');
      }, 100);
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

fadeEls.forEach(el => observer.observe(el));

// ===== STAGGERED CARD ANIMATIONS =====
const cardGroups = document.querySelectorAll('.services-grid, .doctors-grid, .blog-grid');
cardGroups.forEach(group => {
  const cards = group.children;
  const cardObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      Array.from(cards).forEach((card, i) => {
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, i * 120);
      });
      cardObserver.unobserve(group);
    }
  }, { threshold: 0.1 });

  Array.from(cards).forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
  });

  cardObserver.observe(group);
});

// ===== COUNTER ANIMATION =====
function animateCounter(el) {
  const target = parseInt(el.getAttribute('data-target'));
  const suffix = el.getAttribute('data-suffix') || '';
  const duration = 2000;
  const step = target / (duration / 16);
  let current = 0;

  const timer = setInterval(() => {
    current += step;
    if (current >= target) {
      current = target;
      clearInterval(timer);
    }
    el.textContent = Math.floor(current).toLocaleString() + suffix;
  }, 16);
}

const counters = document.querySelectorAll('[data-target]');
const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateCounter(entry.target);
      counterObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });

counters.forEach(counter => counterObserver.observe(counter));


// ===== CONTACT FORM AJAX =====
const contactForm = document.getElementById('contactForm');
if (contactForm) {
  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = contactForm.querySelector('.submit-btn');
    const successEl = contactForm.querySelector('.form-success');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp; Sending…';

    try {
      const res = await fetch('index.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(contactForm),
      });
      const data = await res.json();
      if (data.ok) {
        contactForm.reset();
        if (successEl) { successEl.style.display = 'block'; }
        setTimeout(() => { if (successEl) successEl.style.display = 'none'; }, 5000);
      } else {
        alert('Please fill in all required fields with valid details.');
      }
    } catch {
      alert('Something went wrong. Please try again.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });
}


// ===== ACTIVE NAV LINK ON SCROLL =====
const sections = document.querySelectorAll('section[id]');
const navAnchors = document.querySelectorAll('.nav-links a[href^="#"]');

window.addEventListener('scroll', () => {
  let current = '';
  sections.forEach(section => {
    if (window.scrollY >= section.offsetTop - 120) {
      current = section.getAttribute('id');
    }
  });

  navAnchors.forEach(a => {
    a.style.color = '';
    if (a.getAttribute('href') === '#' + current) {
      a.style.color = '#1565c0';
    }
  });
});
