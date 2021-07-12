/// <reference types="cypress" />

describe('As an Admin I want to see separated content (separator)', () => {
    it('checks if an admin can see separated content (separator)', () => {
        cy.login();
        cy.visit('/bolt/edit/43');
        cy.get('#field-html-html').find('hr').its('length').should('eq', 1);
    })
});

describe('As an Admin I want to see placeholder on new content', () => {
    it('checks if an admin can see placeholder on new content', () => {
        cy.login();
        cy.visit('/bolt/new/showcases');
        cy.get('input[name="fields[title]"]').should('have.attr', 'placeholder').and('match', /Placeholder for the title/);
    })
});

describe('As an Admin, I want to see default values on new content', () => {
    it('checks if an admin can see default values', () => {
        cy.login();
        cy.visit('/bolt');

        cy.get('a[href="/bolt/content/tests"]').trigger('mouseover');
        cy.get('a[href="/bolt/new/tests"]').click({force: true});
        cy.url().should('contain', '/bolt/new/tests');

        cy.get('input[name="fields[title]"]').should('have.value', 'Title of a test contenttype');
        cy.get('input[name="fields[image][filename]"]').should('have.value', 'foal.jpg');

        cy.get('input[name="sets[set_field][title]"]').should('have.value', 'This is the default title value');
        cy.get('input[name="sets[set_field][year]"]').should('have.value', '2020');

        cy.get('input[name="collections[collection_field][photo][1][filename]"]').should('have.value', 'kitten.jpg');
        cy.get('input[name="collections[collection_field][photo][1][alt]"]').should('have.value', 'Cute kitten');

        cy.get('input[name="collections[collection_field][paragraph][2]"]').should('have.value', 'An image, followed by some content');
        
        cy.get('input[name="collections[collection_field][photo][3][filename]"]').should('have.value', 'joey.jpg');
        cy.get('input[name="collections[collection_field][photo][3][alt]"]').should('have.value', 'Photo of a foal');

        cy.get('button[class="btn btn-success mb-0 "]').eq(1).scrollIntoView();
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).click();

        cy.get('input[name="fields[title]"]').should('have.value', 'Title of a test contenttype');
        cy.get('input[name="fields[image][filename]"]').should('have.value', 'foal.jpg');

        cy.get('input[name="sets[set_field][title]"]').should('have.value', 'This is the default title value');
        cy.get('input[name="sets[set_field][year]"]').should('have.value', '2020');

        cy.get('input[name="collections[collection_field][photo][1][filename]"]').should('have.value', 'kitten.jpg');
        cy.get('input[name="collections[collection_field][photo][1][alt]"]').should('have.value', 'Cute kitten');

        cy.get('input[name="collections[collection_field][paragraph][2]"]').should('have.value', 'An image, followed by some content');
        
        cy.get('input[name="collections[collection_field][photo][3][filename]"]').should('have.value', 'joey.jpg');
        cy.get('input[name="collections[collection_field][photo][3][alt]"]').should('have.value', 'Photo of a foal');
    })
});

describe('As an Admin, I want to duplicate a page', () => {
    it('checks if an admin can duplicate a page', () => {
        cy.login();
        cy.visit('/bolt/content/pages');
        cy.get('a[href="/bolt/edit/2"]').should('contain', 'This is a page');

        cy.get('.listing--container:nth-child(1) .btn:nth-child(2)').click();
        cy.get('.show > .dropdown-item:nth-child(4)').click();
        cy.url().should('contain', '/bolt/duplicate/2');

        cy.get('input[name="fields[heading]"]').should('have.value', 'This is a page');
        cy.get('input[name="fields[slug]"]').should('have.value', 'this-is-a-page');

        cy.get('button[class="btn btn-success mb-0 "]').eq(1).scrollIntoView();
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).click();

        cy.get('input[name="fields[heading]"]').should('have.value', 'This is a page');
        cy.get('input[name="fields[slug]"]').should('have.value', 'this-is-a-page-1');
    })
});
