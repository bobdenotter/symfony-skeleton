/// <reference types="cypress" />

describe('As a user I want to see the results of Setcontent', () => {
    it('checks that the Setcontent page is visible as a user', () => {
        
        cy.visit('/page/setcontent-test-page');
        cy.get('#results-one').should('contain', 'yes');
        cy.get('#results-two').should('contain', 'yes');
        cy.get('#results-four').should('contain', 'yes');
        cy.get('#results-five').should('contain', 'yes');
        cy.get('#results-six').should('contain', 'yes');

        cy.get('#three .s1').should('contain', 2);
        cy.get('#three .s2').should('contain', 'published');

        cy.get('main').should('not.contain', '[no]');
    })
});

describe('As a user I want to see the results of Setcontent on a translated page', () => {
    it('checks that the Setcontent is visible on a translated page as a user', () => {
        
        cy.visit('/nl/page/setcontent-test-page');
        cy.get('#results-one').should('contain', 'yes');
        cy.get('#results-two').should('contain', 'yes');
        cy.get('#results-four').should('contain', 'yes');
        cy.get('#results-five').should('contain', 'yes');
        cy.get('#results-six').should('contain', 'yes');

        cy.get('#three .s1').should('contain', 2);
        cy.get('#three .s2').should('contain', 'published');

        cy.get('main').should('not.contain', '[no]');
    })
});
