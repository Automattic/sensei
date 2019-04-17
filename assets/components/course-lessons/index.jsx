import React from 'react';
import ReactDOM from 'react-dom';
import { createElement } from '@wordpress/element';

class CourseOutline extends React.Component {
    constructor( props ) {
        super( props );
        this.apiSettings = window.wpApiSettings;
        this.state = {
            addingNew: false,
            inputText: '',
            orderIndex: 0,
            courseID: props.courseID,
            lessons: []
        };
    }

    componentDidMount() {
        this.fetchCourseOutline();
    }

    fetchCourseOutline() {
        const fetchOptions = {
            headers: {
                'X-WP-Nonce': this.apiSettings.nonce
            }
        };
        window.fetch(this.apiSettings.root + 'sensei-lms-admin/v1/course-outline/' + this.state.courseID, fetchOptions).then(( response ) => {
            if ( response.status !== 200) {
                return window.Promise.reject(['error', response]);
            }
            return response.json();
        }).then((responseJson) => {
            const nextOrderIndex = responseJson.lessons.length;
            this.setState(Object.assign({}, this.state, responseJson, {nextOrderIndex}));
        }).catch(( err ) => {
            console.log( err );
        });
    }

    getFetchApiOptions() {
        this.apiSettings = window.wpApiSettings;
        const fetchOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.apiSettings.nonce
            }
        };
        return fetchOptions;
    }

    createNewLesson( e ) {
        const courseID = this.state.id;
        const inputText = this.state.inputText;
        if ( ! courseID || ! inputText ) {
            return;
        }
        const fetchOptions = Object.assign({},
            this.getFetchApiOptions(), {
            method: 'POST',
                body: JSON.stringify({
                course_id: courseID,
                title: inputText,
                ordering: this.state.nextOrderIndex
            } )
        } );

        window.fetch(this.apiSettings.root + 'sensei-lms-admin/v1/course-outline/' + courseID + '/lessons', fetchOptions).then(( response ) => {
            if ( response.status !== 200) {
                return window.Promise.reject(['error', response]);
            }
            return response.json();
        }).then(( responseJson) => {
            this.setState({inputText: ''});
            this.fetchCourseOutline();
        }).catch(( err ) => {
        });

    }

    trashLesson(lessonID) {
        const fetchOptions = Object.assign({}, this.getFetchApiOptions(), {method: 'DELETE'});
        window.fetch(this.apiSettings.root + 'wp/v2/lessons/' + lessonID, fetchOptions).then(( response ) => {
            if ( response.status !== 200) {
                return window.Promise.reject(['error', response]);
            }
            return response.json();
        }).then((responseJson) => {
            console.log(responseJson)
            this.setState({inputText: ''});
            this.fetchCourseOutline();
        }).catch(( err ) => {
            console.log( err );
        });
    }

    render() {
        const lessons = this.state.lessons;
        const lessonElements = lessons.map(( lesson ) => {
            return (
                createElement('tr', {key: lesson.lesson_id},
                    createElement('td', null,
                        createElement('span', null, lesson.order + ' '),
                        createElement('a', {href: lesson.edit_link}, lesson.title )),
                    createElement('td', null,
                        createElement('a', {href:'#', onClick: ( e ) => { this.trashLesson( lesson.lesson_id )}}, 'Trash')))

            );
        });


        return createElement('div', null,
            createElement( 'table', { className: 'wp-list-table widefat fixed striped posts' },
            createElement( 'tbody', null, lessonElements )),
            createElement( 'hr', null ),
            createElement( 'input', {type: 'text', name: 'add-new-lesson', value: this.state.inputText, onChange: (e) => {
                this.setState({inputText: e.target.value } );
                }}),
            createElement('a', {className: 'button button-primary button-large', onClick: ( e ) => { this.createNewLesson(e); }}, 'Add New')
        );
    }
}


ReactDOM.render( createElement(CourseOutline, {courseID: window.senseiLMSAdminEditCourseID}), document.getElementById( 'sensei-component-course-lesson-container' ) );
