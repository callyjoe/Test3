// Redirect after fade-out
setTimeout(() => {
  const container = document.querySelector('.hexagon-container');
  container.classList.add('fade-out');

  setTimeout(() => {
    window.location.href = 'AI/AI.php';
  }, 1500); // Match the CSS fade-out time
}, 4000);

// Matrix animation
const canvas = document.getElementById('matrix-canvas');
const ctx = canvas.getContext('2d');

canvas.height = window.innerHeight;
canvas.width = window.innerWidth;

const letters = "アァイィウエカキクケコサシスセタチツナニハヒフヘホマミムメヤユラリルレロワヲンABCDEFGHIJKLMNOPQRSTUVWXYZ123456789@#$%^&*()";
const fontSize = 14;
const columns = canvas.width / fontSize;

const drops = Array.from({ length: columns }, () => 1);

function drawMatrix() {
  ctx.fillStyle = "rgba(0, 0, 0, 0.08)";
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  ctx.fillStyle = "#00f0ff";
  ctx.shadowColor = "transparent";
  ctx.shadowBlur = 0;
  ctx.font = `${fontSize}px monospace`;

  for (let i = 0; i < drops.length; i++) {
    const text = letters.charAt(Math.floor(Math.random() * letters.length));
    ctx.fillText(text, i * fontSize, drops[i] * fontSize);

    if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
      drops[i] = 0;
    }
    drops[i]++;
  }
}

setInterval(drawMatrix, 40);

window.addEventListener('resize', () => {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
});
