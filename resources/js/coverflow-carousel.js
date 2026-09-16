export default class CoverflowCarousel {
    constructor(root) {
        this.root = root;
        this.slides = Array.from(root.querySelectorAll(".coverflow__slide"));
        this.restartBtn = root.querySelector("[data-coverflow-restart]");
        this.interval = parseInt(root.dataset.interval || "3000", 10);
        this.currentIndex = 0;
        this.timer = null;

        if (this.slides.length === 0) return;

        this.render();
        this.play();

        this.restartBtn?.addEventListener("click", () => this.restart());

        // Pause on hover, resume on leave (nice UX touch, remove if not wanted)
        root.addEventListener("mouseenter", () => this.pause());
        root.addEventListener("mouseleave", () => this.play());
    }

    render() {
        const total = this.slides.length;
        const half = Math.floor(total / 2);

        this.slides.forEach((slide, i) => {
            slide.classList.remove(
                "is-center",
                "is-left-1",
                "is-left-2",
                "is-right-1",
                "is-right-2",
                "is-hidden",
            );

            let diff = (i - this.currentIndex) % total;
            if (diff > half) diff -= total;
            if (diff < -half) diff += total;

            if (diff === 0) slide.classList.add("is-center");
            else if (diff === 1) slide.classList.add("is-right-1");
            else if (diff === 2) slide.classList.add("is-right-2");
            else if (diff === -1) slide.classList.add("is-left-1");
            else if (diff === -2) slide.classList.add("is-left-2");
            else slide.classList.add("is-hidden");
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

    restart() {
        this.currentIndex = 0;
        this.render();
        this.play();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document
        .querySelectorAll("[data-coverflow]")
        .forEach((el) => new CoverflowCarousel(el));
});
