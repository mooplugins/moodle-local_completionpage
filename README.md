# Course Completion Page for Moodle

A celebratory post-completion experience for Moodle learners. When a learner **officially completes** a course, Course Completion Page can show certificates, achievements, feedback, and next-course suggestions — instead of leaving the journey feeling cut off.

**Product page:** [Course Completion Page – MooPlugins](https://www.mooplugins.com/plugins/?utm_source=github&utm_medium=referral)  
**Documentation:** [MooPlugins docs](https://www.mooplugins.com/docs/?utm_source=github&utm_medium=referral)

## Why Course Completion Page?

Moodle course completion is a powerful tracking feature, but the default learner journey often ends abruptly. There is no built-in place to celebrate completion, surface credentials, collect feedback, or guide the learner to what comes next.

Course Completion Page fills that gap with a configurable, capability-aware landing page that only unlocks when Moodle marks the course complete.

## Features

### Clear completion moment

Shows a celebratory message with course name, completion date, and optional custom headline/message from the teacher or admin.

### Certificates

Detects issued certificates from **Custom certificate** (`mod_customcert`) when installed.

Learners can download and preview issued certificates. The section is hidden when nothing has been issued, or when the certificate activity is hidden from the learner.

### Achievements

Optionally shows:

- Course grade (respects gradebook visibility)
- Course badges
- Time spent from a configurable source:
  - **Time spent plugin** (`local_timespent`) — recommended when installed
  - **Standard logs** (`logstore_standard`) — session-gap estimate only

Missing items are hidden automatically.

### Feedback via Moodle Feedback

Promotes a linked `mod_feedback` activity. This plugin does **not** store learner feedback responses — Moodle’s Feedback module owns collection, reporting, and optional teacher email.

### What’s next + exit paths

Suggest manually curated next courses, and provide exit links to Dashboard, My courses, and Browse courses.

### Site + course configuration

- Site-wide defaults under Local plugins
- Per-course overrides on the course settings form (`Use site default` / Enabled / Disabled)
- When enabled, a **Finish** button on the last activity opens the completion page for learners who have met completion criteria

## Requirements

- Moodle 4.5 or higher (CI matrix targets 4.5, 5.0, 5.2)
- Course completion enabled on the course
- Learner must meet **course completion criteria** (activity progress alone is not enough)
- `local_timespent` is optional and recommended for accurate time-spent reporting; when unavailable, the plugin can estimate time from Moodle standard logs


### Optional integrations

| Integration | Required? | Purpose |
|-------------|-----------|---------|
| `mod_feedback` | Optional (core) | Feedback CTA |
| `mod_customcert` | Optional | Certificates |
| `tool_ecommerce` | Optional | Prices / add-to-cart on suggested courses |
| Core badges / gradebook | Optional | Achievements |
| Standard logs (`logstore_standard`) | Built-in estimate option | Approximate time spent |

## Installation

1. Copy the `completionpage` folder to `local/completionpage` in your Moodle codebase.
2. Visit **Site administration → Notifications** to install/upgrade the plugins.
3. Configure **Site administration → Plugins → Local plugins → Course Completion Page Settings**.
4. Enable course completion and criteria on each course you want to use.
5. Optionally configure per-course settings under **Course administration → Edit settings**.

## Configuration

### Site settings

| Setting | Description |
|---------|-------------|
| Enable | Master on/off; when on, completed learners see Finish on the last activity |
| Section defaults | Message, certificates, achievements, feedback, suggested courses, exit |
| Time spent source | `local_timespent` (default) or standard logs estimate |

### Per-course settings

| Setting | Description |
|---------|-------------|
| Enable completion page | Inherit site default, force on, or force off |
| Custom headline / message | Optional override content |
| Linked Feedback activity | Select a Feedback activity in this course |
| Suggested courses | Comma-separated course IDs |
| Section overrides | Show/hide individual sections |

## How completion gating works

The page is shown only when **all** of the following are true:

1. The learner is logged in (not a guest)
2. The plugin is enabled for the course
3. Course completion tracking is enabled
4. Moodle reports the user as course complete (`completion_info::is_course_complete`)

**Important:** A 100% activity progress bar does **not** unlock this page unless Moodle has also marked course completion.

## Privacy

This plugin stores **per-course configuration only** (display settings, linked Feedback CMID, suggested course IDs). It does not store learner feedback responses, grades, or badge awards — those remain in Moodle core or the relevant activity plugins.

See the privacy provider class for details.

## Capabilities

| Capability | Purpose |
|------------|---------|
| `local/completionpage:view` | View the completion page |
| `local/completionpage:configure` | Configure per-course settings |
| `local/completionpage:manage` | Manage site-wide settings |

## URL

`/local/completionpage/view.php?courseid=COURSEID`

## Changelog

See [CHANGES.md](CHANGES.md).

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).

## Credits

Developed by [MooPlugins](https://www.mooplugins.com/?utm_source=github&utm_medium=referral).
