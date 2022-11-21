import {Controller} from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'eager' */
export default class extends Controller {
  static values = {
    lessonId: String,
  }

  connect() {

    this.count = 0;
    this.element.addEventListener('click', () => {
      this.load()
    });
  }

  load() {
    fetch(`/lesson/${this.lessonIdValue}/complete`,
      {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        method: "POST",
        body: JSON.stringify({})
      })
      .then((response) => {
        if (!response.ok) {
          throw Error(response.statusText);
        }
        document.location = `/lesson/${this.lessonIdValue}`
      }).catch((error) => {
      alert(error)
    });
  }
}
