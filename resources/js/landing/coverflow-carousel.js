class CoverflowCarousel {
    constructor(root) {
        this.root = root;
        this.slides = Array.from(root.querySelectorAll("[data-index]"));
        this.interval = parseInt(root.dataset.interval || "2000", 10);
        this.currentIndex = 0;
        this.timer = null;

        if (this.slides.length === 0) return;

        this.render();

        // Snap into place on first paint, then enable transitions for all later moves
        requestAnimationFrame(() => {
            requestAnimationFrame(() => root.setAttribute("data-ready", ""));
        });

        this.play();
    }

    render() {
        const total = this.slides.length;
        const half = Math.floor(total / 2);

        this.slides.forEach((slide, i) => {
            let diff = (i - this.currentIndex) % total;
            if (diff > half) diff -= total;
            if (diff < -half) diff += total;

            // The position is an attribute; the utilities in coverflow-carousel.blade.php read it.
            let pos = "hidden";
            if (diff === 0) pos = "center";
            else if (diff === 1) pos = "right-1";
            else if (diff === 2) pos = "right-2";
            else if (diff === -1) pos = "left-1";
            else if (diff === -2) pos = "left-2";
            slide.dataset.pos = pos;
        });
    }

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.slides.length;
        this.render();
    }

    play() {
        this.pause();
        this.timer = setInterval(() => this.next(), this.interval);
    }

    pause() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document
        .querySelectorAll("[data-coverflow]")
        .forEach((el) => new CoverflowCarousel(el));
});

export default CoverflowCarousel;
