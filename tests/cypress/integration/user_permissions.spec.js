/// <reference types="cypress" />

describe('As an user I should see nothing in the sidebar menu', () => {
    it('checks if an user can see anything in the sidebar menu', () => {
        cy.login('eddie', 'eddie%1');
        cy.url().should('contain', '/bolt/');
        cy.get('ul[class="admin__sidebar--menu"]').find('li').its('length').should('eq', 3);
    })
});

describe("As an user I should not be able to edit others' articles", () => {
    it("checks if an user can edit others' articles", () => {
        cy.login('eddie', 'eddie%1');
        cy.url().should('contain', '/bolt/');
        cy.get('a[class="btn btn-secondary btn-block btn-sm text-nowrap"]').eq(0).click();
        cy.wait(1000);
        cy.url().should('contain', '/bolt/');
    })
});

describe("As an user I should not be able to use edit actions on others' articles", () => {
    it('checks if an user can use edit actions on articles besides their own', () => {
        cy.login('eddie', 'eddie%1');
        cy.url().should('contain', '/bolt/');

        cy.get('button[class="btn btn-sm btn-secondary edit-actions__dropdown-toggler dropdown-toggle dropdown-toggle-split"]').eq(0).click();
        cy.get('.show > .dropdown-item:nth-child(1)').click();
        cy.wait(1000);
        cy.url().should('contain', '/bolt/');

        cy.get('button[class="btn btn-sm btn-secondary edit-actions__dropdown-toggler dropdown-toggle dropdown-toggle-split"]').eq(0).click();
        cy.get('.show > .dropdown-item:nth-child(2)').click();
        cy.wait(1000);
        cy.get('h1').should('contain', '404 Page not found');

        cy.visit('/bolt/');
        cy.get('button[class="btn btn-sm btn-secondary edit-actions__dropdown-toggler dropdown-toggle dropdown-toggle-split"]').eq(0).click();
        cy.get('.show > .dropdown-item:nth-child(3)').click();
        cy.wait(1000);
        cy.get('h1').should('contain', '404 Page not found');

        cy.visit('/bolt/');
        cy.get('button[class="btn btn-sm btn-secondary edit-actions__dropdown-toggler dropdown-toggle dropdown-toggle-split"]').eq(0).click();
        cy.get('.show > .dropdown-item:nth-child(4)').click();
        cy.visit('/bolt/?page=16');
        cy.get('.listing__records').find('div[class="listing--container is-dashboard"]').its('length').should('eq', 1);

        cy.visit('/bolt/');
        cy.get('button[class="btn btn-sm btn-secondary edit-actions__dropdown-toggler dropdown-toggle dropdown-toggle-split"]').eq(0).click();
        cy.get('.show > .dropdown-item:nth-child(5)').click();
        cy.get('.modal-dialog').should('have.length', 1);
        cy.get('button[class="btn btn-primary bootbox-accept"]').click();
        cy.visit('/bolt/?page=16');
        cy.get('.listing__records').find('div[class="listing--container is-dashboard"]').its('length').should('eq', 1);
    })
});
