/// <reference types="cypress" />

describe('Edit content as chief editor and editor without being the owner', () => {
    it('checks that the chief editor and editor can edit someone else\'s content', () => {
        cy.login('jane_chief', 'jane%1');

        cy.visit('/bolt/content/pages');

        cy.get('.listing__row.is-normal').eq(0).children('.listing__row--item.is-actions.edit-actions').children('div').children('a[href="/bolt/edit/2"]').click();
        cy.url().should('contain', '/bolt/edit/2');

        cy.get('#field-heading').invoke('val').should('contain', 'This is a page');
        cy.get('#field-heading').clear();
        cy.get('#field-heading').type('This is an edited page');

        cy.get('button[name="save"]').eq(1).scrollIntoView();
        cy.get('button[name="save"]').eq(1).click();

        cy.get('#field-heading').invoke('val').should('contain', 'This is an edited page');
        cy.visit('bolt/logout');

        cy.login('john_editor', 'john%1');

        cy.visit('/bolt/content/pages');

        cy.get('.listing__row.is-normal').eq(0).children('.listing__row--item.is-actions.edit-actions').children('div').children('a[href="/bolt/edit/2"]').click();
        cy.url().should('contain', '/bolt/edit/2');

        cy.get('#field-heading').invoke('val').should('contain', 'This is an edited page');
        cy.get('#field-heading').clear();
        cy.get('#field-heading').type('This is a page');

        cy.get('button[name="save"]').eq(1).scrollIntoView();
        cy.get('button[name="save"]').eq(1).click();

        cy.get('#field-heading').invoke('val').should('contain', 'This is a page');
        cy.visit('bolt/logout');
    })
});

describe('Create content as editor and delete it as chief editor', () => {
    it('checks that editors can create content and chief editors can delete it', () => {
        cy.login('john_editor', 'john%1');

        cy.visit('/bolt/content/pages');

        cy.get('.card.mb-3').children('.card-body').children('p').children('a').click();
        cy.url().should('contain', '/bolt/new/pages');

        cy.get('#field-heading').type('Test heading');

        cy.get('button[name="save"]').eq(1).scrollIntoView();
        cy.get('button[name="save"]').eq(1).click();

        cy.visit('/bolt/logout');

        cy.login('jane_chief', 'jane%1');

        cy.visit('/bolt/content/pages?page=3');

        cy.get('.listing__row.is-normal').eq(2).children('.listing__row--item.is-details').children('a').should('contain', 'Test heading -');
        cy.get('button[data-toggle="dropdown"]').eq(3).click();
        cy.get('.edit-actions__dropdown.dropdown-menu.dropdown-menu-right').eq(2).children('a').eq(4).click();
        cy.get('.btn.btn-primary.bootbox-accept').click();

        cy.visit('/bolt/content/pages?page=3');
        cy.get('.listing--container').its('length').should('eq', 2);
    })
});

describe('Change content post status as chief editor', () => {
    it('checks that the chief editor can change a post\'s status', () => {
        cy.login('jane_chief', 'jane%1');

        cy.visit('/bolt/content/pages');

        cy.get('button[data-toggle="dropdown"]').eq(0).click();
        cy.get('.edit-actions__dropdown.dropdown-menu.dropdown-menu-right').eq(0).children('a').eq(1).click({force: true});

        cy.get('.status.is-held').should('exist');

        cy.get('button[data-toggle="dropdown"]').eq(0).click();
        cy.get('.edit-actions__dropdown.dropdown-menu.dropdown-menu-right').eq(0).children('a').eq(0).click({force: true});

        cy.get('.listing--container').eq(0).children('.listing__row.is-normal').children('.listing__row--item.is-meta').children('.status.is-held').should('not.exist');
    })
});
