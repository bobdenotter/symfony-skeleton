/// <reference types="cypress" />

describe('As an Admin I want to be able to make use of the embed field', () => {
    it('checks if an admin can use the embed field', () => {
        cy.login();
        cy.visit('/bolt/edit/44');
        cy.get('a[id="media-tab"]').click();

        cy.get('input[name="fields[embed][url]"]').clear();
        cy.get('input[name="fields[embed][url]"').type('https://www.youtube.com/watch?v=x4IDM3ltTYo');
        cy.wait(2000);
        cy.get('button[class="btn btn-tertiary refresh"]').should('be.enabled');

        cy.get('input[name="fields[embed][title]"]').should('have.value', 'Silversun Pickups - Nightlight (Official Video)');
        cy.get('input[name="fields[embed][authorname]"]').should('have.value', 'Silversun Pickups');
        cy.get('input[name="fields[embed][width]"]').should('have.value', '200');
        cy.get('input[name="fields[embed][height]"]').should('have.value', '113');

        cy.get('.editor__embed .remove').click();
        cy.get('input[name="fields[embed][title]"]').should('have.value', '');
        cy.get('input[name="fields[embed][authorname]"]').should('have.value', '');
        cy.get('input[name="fields[embed][width]"]').should('have.value', '');
        cy.get('input[name="fields[embed][height]"]').should('have.value', '');
    })
});

describe('As an Admin I want to see the field infobox', () => {
    it('checks if an admin can see the infobox field', () => {
        cy.login();
        cy.visit('/bolt/edit/38');

        cy.get("label[for='field-email']").should('exist');
        cy.get("label[for='field-email']").find('i').its('length').should('eq', 1);

        cy.get('label[for="field-email"]').scrollIntoView();
        cy.get("label[for='field-email'] > i").trigger('mouseover');
        cy.get('.popover-header').should('contain', 'Email').should('be.visible');
        cy.get('.popover-body').should('contain', 'This is an info box shown as a popover next to the field label.').should('be.visible');
    })
});

describe('As an Admin, I want to reset an image field', () => {
    it('checks if an admin can reset an image field', () => {
        cy.login();
        cy.visit('/bolt/edit/40');
        cy.get('a[id="media-tab"]').click();

        cy.get("label[for=field-image]").should('contain', 'Image');
        cy.get('input[name="fields[image][filename]"]').should('have.value', 'puppy2.jpg');
        cy.get('input[name="fields[image][alt]"]').should('have.value', 'Qui vel id expedita voluptates labore.');

        cy.get('button[class="btn btn-sm btn-hidden-danger"]').should('contain', 'Remove').eq(0).click();
        cy.get('input[name="fields[image][filename]"]').should('have.value', '');
        cy.get('input[name="fields[image][alt]"]').should('have.value', '');
    })
});
