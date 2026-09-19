// Animate the core pulse
anime({
  targets: '#core',
  opacity: [0, 1],
  scale: [0.3, 1],
  duration: 1000,
  easing: 'easeOutElastic(1, .5)'
});

// Animate ring expansions
anime({
  targets: '.ring',
  opacity: [0, 1],
  scale: [0.8, 1],
  delay: anime.stagger(200, { start: 800 }),
  duration: 1200,
  easing: 'easeOutExpo'
});

// Circuit lines firing out
anime({
  targets: '.circuit',
  opacity: [0, 1],
  translateY: [-10, 0],
  translateX: [-10, 0],
  delay: anime.stagger(150, { start: 1400 }),
  duration: 900,
  easing: 'easeOutSine'
});

// Node pulses
anime({
  targets: '.node',
  opacity: [0, 1],
  scale: [0.5, 1],
  delay: anime.stagger(150, { start: 1800 }),
  duration: 800,
  easing: 'easeOutElastic(1, .6)'
});

// Show text
anime({
  targets: '.ai-title',
  opacity: [0, 1],
  duration: 1000,
  delay: 2400,
  easing: 'easeOutExpo'
});

// Redirect after total animation completes
setTimeout(() => {
  window.location.href = 'signup.html';
}, 5000); // Adjust duration as needed
