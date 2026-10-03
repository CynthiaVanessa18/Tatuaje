"use strict";

const header = document.querySelector("#siteHeader");
const menuButton = document.querySelector("#menuButton");
const mainNav = document.querySelector("#mainNav");
const revealElements = document.querySelectorAll(".reveal");

function updateHeader() {
    header?.classList.toggle("is-scrolled", window.scrollY > 30);
}

menuButton?.addEventListener("click", () => {
    const isOpen = mainNav?.classList.toggle("is-open") ?? false;

    menuButton.setAttribute("aria-expanded", String(isOpen));
    menuButton.setAttribute(
        "aria-label",
        isOpen ? "Cerrar menú" : "Abrir menú"
    );
});

mainNav?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
        mainNav.classList.remove("is-open");
        menuButton?.setAttribute("aria-expanded", "false");
        menuButton?.setAttribute("aria-label", "Abrir menú");
    });
});

document.addEventListener("pointermove", (event) => {
    document.documentElement.style.setProperty(
        "--mouse-x",
        `${event.clientX}px`
    );

    document.documentElement.style.setProperty(
        "--mouse-y",
        `${event.clientY}px`
    );
});

const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
        });
    },
    {
        threshold: 0.12
    }
);

revealElements.forEach((element) => observer.observe(element));

updateHeader();
window.addEventListener("scroll", updateHeader, { passive: true });