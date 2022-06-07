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
            link.setAttribute('href', href.split('#')[0]);
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

if (customElements && customElements.define) {
    customElements.define('scroll-into-view', ScrollIntoView);
}

/* disable submit button on form submit */
document.addEventListener('submit', function (e) {
    e.target.querySelector('[type="submit"]').disabled = true;
});


async function handleMealFormSubmit(form) {
    const wrapper = form.closest('.meal')

    let response = await fetch(form.getAttribute('action'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            "X-CSRF-TOKEN": window.Futtertrog.csrf,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: new FormData(form)
    });

    const tmp = document.createElement('div');
    tmp.innerHTML = await response.text();
    const meal = tmp.querySelector('.meal');

    wrapper.innerHTML = meal.innerHTML;
    wrapper.classList = meal.classList;

}


/* are you sure modal on delete form */
var handleFormSubmit = function (e) {
    const form = e.target
    if (form.matches('.meal-form')) {
        e.preventDefault();
        return handleMealFormSubmit(form);
    }

    if (form.querySelector('[name="_method"][value="delete"]')) {
        if ((confirm(window.Futtertrog.messages.are_you_sure))) {
            e.target.submit();
        } else {
            e.preventDefault();
            e.target.querySelector('[type="submit"]').disabled = false;
        }
    }
};

document.addEventListener('submit', handleFormSubmit);
