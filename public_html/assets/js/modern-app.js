// Modern JavaScript with Advanced Animations and Interactions

class ModernApp {
  constructor() {
    this.initializeApp();
    this.setupEventListeners();
    this.initAnimations();
    this.initCursor();
    this.initParallax();
    this.initSmoothScroll();
    this.initLazyLoading();
    this.initThemeToggle();
  }

  initializeApp() {
    // Add loading animation
    this.showLoader();
    
    // Initialize AOS (Animate On Scroll) if available
    if (typeof AOS !== 'undefined') {
      AOS.init({
        duration: 1000,
        once: true,
        offset: 100
      });
    }

    // Remove loader after content loads
    window.addEventListener('load', () => {
      setTimeout(() => this.hideLoader(), 500);
    });
  }

  showLoader() {
    const loader = document.createElement('div');
    loader.className = 'loader-container';
    loader.innerHTML = '<div class="modern-loader"></div>';
    document.body.appendChild(loader);
  }

  hideLoader() {
    const loader = document.querySelector('.loader-container');
    if (loader) {
      loader.style.opacity = '0';
      setTimeout(() => loader.remove(), 300);
    }
  }

  setupEventListeners() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle && navMenu) {
      menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
      });
    }

    // Close mobile menu on link click
    document.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (navMenu?.classList.contains('active')) {
          navMenu.classList.remove('active');
          menuToggle?.classList.remove('active');
        }
      });
    });
  }

  initAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in');
          
          // Stagger animations for child elements
          const children = entry.target.querySelectorAll('.animate-child');
          children.forEach((child, index) => {
            setTimeout(() => {
              child.classList.add('animate-in');
            }, index * 100);
          });
        }
      });
    }, observerOptions);

    // Observe all animatable elements
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
      observer.observe(el);
    });

    // Add floating animation to gradient orbs
    this.animateGradientOrbs();
  }

  animateGradientOrbs() {
    const orbs = document.querySelectorAll('.gradient-orb');
    
    orbs.forEach((orb, index) => {
      // Random movement animation
      const animateOrb = () => {
        const randomX = Math.random() * 200 - 100;
        const randomY = Math.random() * 200 - 100;
        const duration = 15 + Math.random() * 10;
        
        orb.animate([
          { transform: `translate(${randomX}px, ${randomY}px)` }
        ], {
          duration: duration * 1000,
          direction: 'alternate',
          iterations: Infinity,
          easing: 'ease-in-out'
        });
      };
      
      setTimeout(animateOrb, index * 1000);
    });
  }

  initCursor() {
    // Skip custom cursor on touch devices
    if ('ontouchstart' in window) return;

    const cursor = document.createElement('div');
    const cursorFollower = document.createElement('div');
    cursor.className = 'cursor';
    cursorFollower.className = 'cursor-follower';
    
    document.body.appendChild(cursor);
    document.body.appendChild(cursorFollower);

    let mouseX = 0, mouseY = 0;
    let cursorX = 0, cursorY = 0;
    let followerX = 0, followerY = 0;

    document.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
    });

    // Smooth cursor animation
    const animateCursor = () => {
      // Main cursor
      const dx = mouseX - cursorX;
      const dy = mouseY - cursorY;
      cursorX += dx * 0.9;
      cursorY += dy * 0.9;
      cursor.style.left = cursorX + 'px';
      cursor.style.top = cursorY + 'px';

      // Follower
      const fdx = mouseX - followerX;
      const fdy = mouseY - followerY;
      followerX += fdx * 0.4;
      followerY += fdy * 0.4;
      cursorFollower.style.left = followerX + 'px';
      cursorFollower.style.top = followerY + 'px';

      requestAnimationFrame(animateCursor);
    };
    animateCursor();

    // Cursor interactions
    const interactiveElements = document.querySelectorAll('a, button, .interactive');
    
    interactiveElements.forEach(el => {
      el.addEventListener('mouseenter', () => {
        cursor.style.transform = 'translate(-50%, -50%) scale(1.5)';
        cursorFollower.style.transform = 'translate(-50%, -50%) scale(1.5)';
      });
      
      el.addEventListener('mouseleave', () => {
        cursor.style.transform = 'translate(-50%, -50%) scale(1)';
        cursorFollower.style.transform = 'translate(-50%, -50%) scale(1)';
      });
    });
  }

  initParallax() {
    const parallaxElements = document.querySelectorAll('.parallax');
    
    if (parallaxElements.length === 0) return;

    window.addEventListener('scroll', () => {
      const scrolled = window.pageYOffset;
      
      parallaxElements.forEach(el => {
        const speed = el.dataset.speed || 0.5;
        const yPos = -(scrolled * speed);
        el.style.transform = `translateY(${yPos}px)`;
      });
    });
  }

  initSmoothScroll() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        
        if (target) {
          const headerOffset = 80;
          const elementPosition = target.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
          });
        }
      });
    });

    // Add scroll progress indicator
    this.initScrollProgress();
  }

  initScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 0%;
      height: 3px;
      background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
      z-index: 9999;
      transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrolled = (winScroll / height) * 100;
      progressBar.style.width = scrolled + '%';
    });
  }

  initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.add('loaded');
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
          }
        });
      });

      images.forEach(img => imageObserver.observe(img));
    } else {
      // Fallback for browsers that don't support IntersectionObserver
      images.forEach(img => {
        img.src = img.dataset.src;
        img.classList.add('loaded');
      });
    }
  }

  initThemeToggle() {
    const themeToggle = document.querySelector('.theme-toggle');
    const body = document.body;
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme') || 'dark';
    body.classList.toggle('light-mode', savedTheme === 'light');
    
    if (themeToggle) {
      themeToggle.addEventListener('click', () => {
        body.classList.toggle('light-mode');
        const currentTheme = body.classList.contains('light-mode') ? 'light' : 'dark';
        localStorage.setItem('theme', currentTheme);
        
        // Add transition effect
        this.animateThemeTransition();
      });
    }
  }

  animateThemeTransition() {
    const transition = document.createElement('div');
    transition.className = 'page-transition active';
    document.body.appendChild(transition);
    
    setTimeout(() => {
      transition.classList.remove('active');
      setTimeout(() => transition.remove(), 500);
    }, 300);
  }

  // Text animation utilities
  animateText(element, delay = 50) {
    const text = element.textContent;
    element.textContent = '';
    element.style.opacity = '1';
    
    [...text].forEach((char, i) => {
      const span = document.createElement('span');
      span.textContent = char === ' ' ? '\u00A0' : char;
      span.style.opacity = '0';
      span.style.display = 'inline-block';
      span.style.animation = `fadeInUp 0.5s ease forwards`;
      span.style.animationDelay = `${i * delay}ms`;
      element.appendChild(span);
    });
  }

  // Number counter animation
  animateCounter(element, target, duration = 2000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    
    const updateCounter = () => {
      current += increment;
      if (current < target) {
        element.textContent = Math.floor(current).toLocaleString();
        requestAnimationFrame(updateCounter);
      } else {
        element.textContent = target.toLocaleString();
      }
    };
    
    updateCounter();
  }

  // Magnetic button effect
  initMagneticButtons() {
    const magneticButtons = document.querySelectorAll('.btn-magnetic');
    
    magneticButtons.forEach(btn => {
      btn.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        
        btn.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
      });
      
      btn.addEventListener('mouseleave', () => {
        btn.style.transform = 'translate(0, 0)';
      });
    });
  }

  // 3D card tilt effect
  init3DCards() {
    const cards = document.querySelectorAll('.card-3d');
    
    cards.forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const rotateX = (y - centerY) / 10;
        const rotateY = (centerX - x) / 10;
        
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
      });
      
      card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
      });
    });
  }

  // Initialize GSAP animations if available
  initGSAPAnimations() {
    if (typeof gsap === 'undefined') return;
    
    // Hero title animation
    gsap.from('.hero-title', {
      duration: 1.5,
      y: 100,
      opacity: 0,
      ease: 'power4.out'
    });
    
    // Stagger feature cards
    gsap.from('.feature-card', {
      duration: 1,
      y: 50,
      opacity: 0,
      stagger: 0.2,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: '.feature-grid',
        start: 'top 80%'
      }
    });
  }
}

// Initialize the app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const app = new ModernApp();
  
  // Expose some methods globally if needed
  window.modernApp = {
    animateText: app.animateText.bind(app),
    animateCounter: app.animateCounter.bind(app)
  };
});

// Page visibility API for performance
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    // Pause animations when page is not visible
    document.querySelectorAll('.gradient-orb').forEach(orb => {
      orb.style.animationPlayState = 'paused';
    });
  } else {
    // Resume animations
    document.querySelectorAll('.gradient-orb').forEach(orb => {
      orb.style.animationPlayState = 'running';
    });
  }
});

// Preload critical resources
const preloadResources = () => {
  const resources = [
    '/assets/img/hero-bg.webp',
    '/assets/fonts/inter-var.woff2'
  ];
  
  resources.forEach(resource => {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.href = resource;
    link.as = resource.endsWith('.woff2') ? 'font' : 'image';
    if (resource.endsWith('.woff2')) {
      link.crossOrigin = 'anonymous';
    }
    document.head.appendChild(link);
  });
};

// Performance monitoring
const measurePerformance = () => {
  if ('performance' in window) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        const perfData = performance.getEntriesByType('navigation')[0];
        console.log('Page Load Time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
      }, 0);
    });
  }
};

// Initialize performance optimizations
preloadResources();
measurePerformance();