/// <reference types="cypress" />

describe('As a chief editor I should be able to clear cache', () => {
    it('checks if a chief editor is able to clear cache', () => {
        cy.login('jane_chief', 'jane%1');
        cy.url().should('contain', '/bolt/');
        cy.get('ul[class="admin__sidebar--menu"]').find('li').eq(10).trigger('mouseover');

        cy.get('a[href="/bolt/menu/maintenance"]').click();
        cy.url().should('contain', '/bolt/menu/maintenance');
        cy.get('.menupage').find('ul').find('li').its('length').should('eq', 2);
        cy.get('.menupage').find('ul').find('li').eq(1).click();
        cy.url().should('contain', '/bolt/about');

        cy.visit('/bolt/menu/maintenance');
        cy.get('.menupage').find('ul').find('li').eq(0).click();
        cy.url().should('contain', '/bolt/clearcache');

        cy.visit('/bolt/extensions');
        cy.url().should('contain', '/bolt/');
        cy.url().should('not.contain', '/bolt/extensions');

        cy.visit('/bolt/logviewer');
        cy.url().should('contain', '/bolt/');
        cy.url().should('not.contain', '/bolt/logviewer');

        cy.visit('/bolt/api', {failOnStatusCode: false});
        cy.get('h1').should('contain', '403 Forbidden');

        cy.visit('/bolt/_trans', {failOnStatusCode: false});
        cy.get('h1').should('contain', '403 Forbidden');

        cy.visit('/bolt/kitchensink');
        cy.url().should('contain', '/bolt/');
        cy.url().should('not.contain', '/bolt/kitchensink');
    })
});
