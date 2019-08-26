class ExpandableMenu {
    constructor(nav) {
        this.nav = nav;

        this.nav.setAttribute('class', 'js');
        this.createToggleButton();
    }

    createToggleButton() {
        if(!!this.nav.getElementsByTagName('ul')[0]){
            this.toggleButton = document.createElement('button');
            this.toggleButton.insertAdjacentHTML('afterbegin',
                '<span aria-hidden="true">&rarr;</span>' +
                '<span class="sr-only">Menü</span>'
            );
            this.toggleButton.setAttribute('aria-haspopup', 'true');
            this.toggleButton.setAttribute('aria-expanded', 'false');

            this.nav.insertAdjacentElement('afterbegin', this.toggleButton);
            this.toggleButton.addEventListener('click', this.onButtonClick.bind(this));
        }
    }

    onButtonClick() {
        if(this.isExpanded()) {
            this.toggleButton.setAttribute('aria-expanded', 'false');
            this.toggleButton.querySelector('[aria-hidden]').innerHTML = '&larr;';
        } else {
            this.toggleButton.setAttribute('aria-expanded', 'true');
            this.toggleButton.querySelector('[aria-hidden]').innerHTML = '&rarr;';
        }
    }

    isExpanded() {
        return this.toggleButton.getAttribute('aria-expanded') === 'true';
    }

}

/* Initialising instances */
if(!!document.getElementById('main-navbar')) {
    new ExpandableMenu(document.getElementById('main-navbar'));
}
