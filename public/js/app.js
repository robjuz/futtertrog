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

/* are you sure modal on delete form */
var confirmDelete = function (e) {
    if (e.target.querySelector('[name="_method"][value="delete"]')) {
        if ((confirm(window.Futtertrog.messages.are_you_sure))) {
            e.target.submit();
        } else {
            e.preventDefault();
            e.target.querySelector('[type="submit"]').disabled = false;
        }
    }
};
document.addEventListener('submit', confirmDelete);






async function init(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        response = await fetch('order_items/json', {
            method: 'POST',
            credentials: 'same-origin',
            redirect: 'follow',
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": window.Futtertrog.csrf,
                "X-Requested-With": "XMLHttpRequest",
                "Content-type": "application/json"
            },
            body: JSON.stringify(formData)
        });
        console.log(response);
    } catch (error) {
        console.log(error);
    }
    }



document.querySelectorAll('.meal-form').forEach(item => {
    item.addEventListener('submit', event => {
        init(event); })})

