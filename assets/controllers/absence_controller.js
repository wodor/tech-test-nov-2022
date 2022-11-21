import {Controller} from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    studentId: String,
    lessonId: String,
    groupId: String,
  }

  connect() {

    this.count = 0;
    this.element.addEventListener('click', () => {
      this.load()
    });
  }

  load() {
    fetch(`/group/${this.groupIdValue}/lesson/${this.lessonIdValue}/absence/${this.studentIdValue}`,
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
        document.getElementById("student-presence-" + this.studentIdValue).hidden = true;
      }).catch((error) => {
      alert(error)
    });
  }
}
