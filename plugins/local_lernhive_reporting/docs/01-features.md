# local_lernhive_reporting — Features

## Purpose

Offer a simple reporting dashboard on top of Moodle reports and analytics.

## Release 1 features

1. **Users in course tile**
- shows active enrolled users for the selected course

2. **Popular courses tile**
- shows the top course by active participants
- drilldown lists top courses by participant count

3. **Course completion tile**
- shows completion rate for the selected course
- drilldown lists completion stats for top courses

4. **Course filter**
- user selects a course to update tile values

5. **Explicit empty states**
- no participants in selected course
- no completion records yet
- no drilldown rows yet

## Data model policy (R1)

- read-only access to Moodle core tables
- no custom reporting tables
- no background aggregation jobs

## Release 2 ideas (not in R1)

- inactive users tile
- content usage trends
- community activity insights
- richer segmentation by Audience
