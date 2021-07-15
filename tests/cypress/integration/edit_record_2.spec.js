/// <reference types="cypress" />

<<<<<<< Updated upstream
describe('As an Admin I want to preview an edited record', () => {
    it('checks if an admin can preview an edited record', () => {
        cy.login();
        cy.visit('/bolt/edit/30');
        cy.get('input[id="field-title"]').clear();
        cy.get('input[id="field-title"]').type('Check preview');

        cy.get('button[formaction="/bolt/preview/30"]').eq(1).scrollIntoView();
        cy.get('form[id="editcontent"]').submit();
        cy.request({url: '/bolt/preview/30', failOnStatusCode: false}).then((response) => {cy.get('h1').should('contain', 'Check preview')});


        cy.visit('/bolt/edit/30');
        cy.reload();
        cy.wait(1000);
        cy.get('input[id="field-title"]').should('not.contain', 'Check preview');
    })
});
=======
// describe('As an Admin I want to preview an edited record', () => {
//     it('checks if an admin can preview an edited record', () => {
//         cy.login();
//         cy.visit('/bolt/edit/30');
//         cy.get('input[id="field-title"]').clear();
//         cy.get('input[id="field-title"]').type('Check preview');

//         cy.get('button[formaction="/bolt/preview/30"]').eq(1).scrollIntoView();
//         cy.get('form[id="editcontent"]').then(($el) => {
//             const form = $el.get(0);
//             var data = new FormData(form);
//             cy.request({url: '/bolt/preview/30', body: data, failOnStatusCode: false}).then((response) => {
//                 expect(response.body).to.contain('Check preview');
//             });
//         });

//         cy.visit('/bolt/edit/30');
//         cy.reload();
//         cy.wait(1000);
//         cy.get('input[id="field-title"]').should('not.have.value', 'Check preview');
//     })
// });
>>>>>>> Stashed changes

describe('As an Admin I want to view saved changes of a record', () => {
    it('checks if an admin can view saved changes on a record', () => {
        cy.login();
        cy.visit('/bolt/edit/2');
        cy.get('input[id="field-heading"]').clear();
        cy.get('input[id="field-heading"]').type('This is the title in the wrong locale');
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).scrollIntoView();
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).click();

        cy.visit('/bolt/edit/2?edit_locale=nl');
        cy.get('input[id="field-heading"]').clear();
        cy.get('input[id="field-heading"]').type('This is the title in the right locale');
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).scrollIntoView();
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).click();

        cy.url().should('contain', '/bolt/edit/2?edit_locale=nl');
        cy.get('a[class="btn btn-tertiary btn-sm"]').scrollIntoView();
        cy.get('a[class="btn btn-tertiary btn-sm"]').click();

        cy.visit('/nl/page/this-is-a-page');
        cy.get('h1').should('not.contain', 'This is the title in the wrong locale');
        cy.get('h1').should('contain', 'This is the title in the right locale');
    })
});
