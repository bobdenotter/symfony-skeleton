class NewRecordPage extends EditRecordPage {
  constructor() {
    super();

    this.url = '/bolt/new/:contentType';
  }
}

module.exports = NewRecordPage;
