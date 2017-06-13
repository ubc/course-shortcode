=== Plugin Template ===
Plugin Name: UBC Courses
Plugin URI: https://github.com/ubc/course-shortcode
Description: Allows the listing of UBC courses and sections with data from the UBC calendar.
Version: 1.0.2
Author: Michael Ha (CTLT) and Shaffiq Rahemtulla (ArtsISIT)
Author URI: http://isit.arts.ubc.ca
License: GPL3

A simple WordPress plugin that allows the listing of UBC courses, sections and instructors with data from the UBC calendar.

== Description ==

A simple OOP WordPress plugin that allows the listing of UBC courses, sections and instructors with data from the UBC calendar. This plugin comes with an options page "UBC Courses" with form submission, a route handler (for custom submission, AJAX and page rendering), activation and deactivation actions, automatic admin panel JavaScript and CSS loading, string sanitization function, further validation constraints on allowable option size and two main shortcodes, "ubccourses" and "ubcinstructors". 

The continuing operation of this plugin is dependent on the good graces of the folks at UBCIT who provide the necessary business functions to serve up the data.

== Features ==

Uses the WordPress Transients API throughout the coding as a way of storing cached Calendar data in the database temporarily and reduces the amount of traffic to and from the UBC Calendar server. 

== [ubccourses] shortcode ==

In its basic form the [ubccourses] shortcode allows the listing of UBC courses with data obtained from the UBC Calendar. Some of the allowed parameters are as below:

 - department (default ” – if left empty will show example of usage)
 - course (default ” – if department filled and course empty then you get all depatmental courses listed)
 - tabs (default false – if true, the data is setup with tabs for each level of courses e.g. 100 year level, 200 year level etc)
 - pills (default false – if true, the data is setup with pills for each level of courses e.g. 100 year level, 200 year level etc)
 - tabcount (default 4 – if true, the data is truncated  at the level level of courses e.g. 400 year level)
 - parentslug (default ” – if entered, any page title (of the form e.g. “ANTH201A” that matches has has a parent equal to the slug will be linked to from the list with a “Details” button
 - opentab (**New parameter not in production – default 1 – has to be between 1 and tabcount – if entered will auto open at that tab/pill
 - profileslug (**New parameter not in production – default ” – if entered and a profile exists on the website, shows a link next to instructors name in the sections listing.
 - stickywinter (**New parameter not in production – default ‘false’ – if true session remains as Winter even if Summer term has begun.
 - stickyyear (default false - if true, then the year is forced to be this year, which fixes a bug that causes the previous year's summer courses to be listed instead of the current year's)
 - instructors (default false - if true and plugin configured (via the settings panel), will list instructors on the main listing page (without users having to click on the "sections" button to see them).
 - fuzzy (default on and set to 80% - does fuzzy matching of instructor names (80% usually "fixes" special char issues in a name))

== [ubcinstructors] shortcode ==

In its basic form the [ubccourses instructorname={name}] shortcode allows the listing of UBC courses that the instructor teaches in the current session. The instructor name has to match exactly with the name in the UBC Calendar. 

If used without the instructorname parameter and on a profile singular page, will show courses taught by that instrucor.

Some of the allowed parameters are as below:

 - instructorname (default '' - if entered and plugin configured (via the settings panel), will list all courses that an instructor teaches within the current session)
 - parentslug (default ” – if entered, any page title (of the form e.g. “ANTH201A” that matches has has a parent equal to the slug will be linked to from the list with a “Details” button
 - profileslug (**New parameter not in production – default ” – if entered and a profile exists on the website, shows a link next to instructors name in the sections listing.
 - stickywinter (**New parameter not in production – default ‘false’ – if true session remains as Winter even if Summer term has begun.
 - stickyyear (default false - if true, then the year is forced to be this year, which fixes a bug that causes the previous year's summer courses to be listed instead of the current year's)
 - instructors (default false - if true and plugin configured (via the settings panel), will list instructors on the main listing page (without users having to click on the "sections" button to see them.


== Installation ==

The plugin is simple to install:

1. Download `ubccourses.zip`
1. Unzip
1. Upload `plugin-template` directory to your `/wp-content/plugins` directory
1. Go to the plugin management page and enable the plugin

== Changelog ==

= 1.0 =
* Initial release
 - Added admin page under Settings to collect instructor data.
 - Added shortcode [ubcinstructors] to display courses by instructor name.
