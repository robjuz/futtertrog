class ExpandableMenu {
    constructor(nav) {
        this.nav = nav;

        this.nav.classList.add('js');
        this.createToggleButton();
        this.updateLinks();
    }

    createToggleButton() {
        if (!!this.nav.getElementsByTagName('ul')[0]) {
            this.toggleButton = document.createElement('button');
            this.toggleButton.innerHTML = this.nav.getAttribute('data-button');
            this.toggleButton.setAttribute('aria-haspopup', 'true');
            this.toggleButton.setAttribute('aria-expanded', 'false');

            this.nav.insertAdjacentElement('afterbegin', this.toggleButton);
            this.toggleButton.addEventListener('click', this.onButtonClick.bind(this));
        }
    }

    updateLinks() {
        this.nav.querySelectorAll('a').forEach(function (link) {
            let href = link.getAttribute('href');
            link.setAttribute('href', href.substring(0, href.indexOf('#')));
        });
    }

    onButtonClick() {
        if (this.isExpanded()) {
            this.toggleButton.setAttribute('aria-expanded', 'false');
        } else {
            this.toggleButton.setAttribute('aria-expanded', 'true');
        }
    }

    isExpanded() {
        return this.toggleButton.getAttribute('aria-expanded') === 'true';
    }

}

/* Initialising instances */
if (!!document.getElementById('main-navbar')) {
    new ExpandableMenu(document.getElementById('main-navbar'));
}

/* auto hide success message */
window.onload = function() {
    setTimeout(function () {
        Array.from(document.querySelectorAll('.success-message')).forEach(node => node.remove());
    }, 3000);
};

class ScrollIntoView extends HTMLElement {
    constructor() {
        super();
        this.current = this.querySelector('.selected');
        this.scrollIntoView();

        window.addEventListener('resize', this.scrollIntoView.bind(this));

    }

    scrollIntoView() {
        const halfWindow = window.innerWidth / 2;
        const currentElementWidth = this.current.getBoundingClientRect().width;
        this.current.parentElement.scrollLeft += this.current.getBoundingClientRect().left - halfWindow + currentElementWidth / 2;
    }

}

customElements.define('scroll-into-view', ScrollIntoView);
