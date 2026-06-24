function initHeroStars() {
    const canvas = document.getElementById('saas-starter-hero-stars');
    if (!(canvas instanceof HTMLCanvasElement)) {
        return;
    }

    const ctx = canvas.getContext('2d', { alpha: true });
    if (!ctx) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const colors = ['#e5edff', '#b8c7ff', '#93c5fd', '#f8fafc'];
    const nebulaColors = [
        'rgba(30, 64, 175, 0.10)',
        'rgba(14, 116, 144, 0.08)',
        'rgba(15, 23, 42, 0.20)',
    ];

    let width = 0;
    let height = 0;
    let pixelRatio = 1;
    let animationFrame = 0;
    let stars = [];
    let visible = !document.hidden;

    function starCountForViewport() {
        if (width < 640) {
            return 70;
        }

        if (width < 1024) {
            return 120;
        }

        return 165;
    }

    function createStar() {
        const depth = Math.random();

        return {
            x: Math.random() * width,
            y: Math.random() * height,
            radius: 0.35 + Math.random() * 1.1,
            alpha: 0.2 + Math.random() * 0.55,
            speed: 0.012 + depth * 0.045,
            drift: (Math.random() - 0.5) * 0.025,
            phase: Math.random() * Math.PI * 2,
            twinkle: 0.001 + Math.random() * 0.002,
            color: colors[Math.floor(Math.random() * colors.length)],
        };
    }

    function resize() {
        const rect = canvas.getBoundingClientRect();
        width = Math.max(1, Math.floor(rect.width));
        height = Math.max(1, Math.floor(rect.height));
        pixelRatio = Math.min(window.devicePixelRatio || 1, 2);

        canvas.width = Math.floor(width * pixelRatio);
        canvas.height = Math.floor(height * pixelRatio);
        ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);

        stars = Array.from({ length: starCountForViewport() }, createStar);
        draw(0);
    }

    function drawNebula(time) {
        nebulaColors.forEach((color, index) => {
            const x = width * (index === 1 ? 0.72 : 0.28) + Math.sin(time * 0.00008 + index) * 18;
            const y = height * (index === 2 ? 0.72 : 0.28) + Math.cos(time * 0.00007 + index) * 16;
            const size = Math.max(width, height) * (0.55 + index * 0.12);
            const gradient = ctx.createRadialGradient(x, y, 0, x, y, size);

            gradient.addColorStop(0, color);
            gradient.addColorStop(0.55, 'rgba(15, 23, 42, 0.04)');
            gradient.addColorStop(1, 'rgba(15, 23, 42, 0)');

            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(x, y, size, 0, Math.PI * 2);
            ctx.fill();
        });
    }

    function draw(time) {
        ctx.clearRect(0, 0, width, height);
        drawNebula(time);

        stars.forEach((star) => {
            const twinkle = reduceMotion ? 1 : 0.72 + Math.sin(time * star.twinkle + star.phase) * 0.28;

            ctx.globalAlpha = star.alpha * twinkle;
            ctx.fillStyle = star.color;
            ctx.beginPath();
            ctx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalAlpha = 1;

            if (!reduceMotion) {
                star.y += star.speed;
                star.x += star.drift;

                if (star.y > height + 4) {
                    star.y = -4;
                    star.x = Math.random() * width;
                }

                if (star.x < -4) {
                    star.x = width + 4;
                } else if (star.x > width + 4) {
                    star.x = -4;
                }
            }
        });
    }

    function animate(time) {
        if (visible) {
            draw(time);
        }

        if (!reduceMotion) {
            animationFrame = window.requestAnimationFrame(animate);
        }
    }

    function handleVisibilityChange() {
        visible = !document.hidden;
    }

    resize();
    window.addEventListener('resize', resize, { passive: true });
    document.addEventListener('visibilitychange', handleVisibilityChange);

    if (!reduceMotion) {
        animationFrame = window.requestAnimationFrame(animate);
    }

    window.addEventListener('beforeunload', () => {
        window.cancelAnimationFrame(animationFrame);
        window.removeEventListener('resize', resize);
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeroStars);
} else {
    initHeroStars();
}
