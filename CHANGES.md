# Changelog

All notable changes to the Course Completion Page plugin are documented here.

## 1.2.32 - 2026-08-20

### Changed

- Removed optional platform-specific time-spent visibility gate. Time spent is
  shown whenever a value is available from the configured source.

## 1.2.31 - 2026-08-20

### Fixed

- Unavailable section checkboxes now persist their default on first save so
  Site administration → New settings can complete when an optional plugin
  (for example Custom certificate) is not installed.

## 1.2.30 - 2026-08-20

### Added

- `pix/image_placeholder.svg` for suggested course cards when a course has no
  overview image.
- Self-contained suggested course card styles in `styles.css` (works on standard
  Moodle themes such as Boost without a custom LMS theme).

### Changed

- Suggested course cards show the bundled placeholder icon instead of Font Awesome
  or a generic JPEG fallback.
- Optional integrations documented and soft-detected: `local_timespent`,
  `mod_customcert`, and ecommerce coming-soon notice (no hard
  `$plugin->dependencies`; time spent falls back to standard logs when Time spent
  is not installed).

## 1.2.29 - 2026-08-20

### Fixed

- PHPDoc for `suggested_courses::get_same_category_courseids()` (CI moodlecheck).
- Moodle stylelint compliance in `styles.css` (removed `!important`, invalid
  `clamp()`, long hex colours).
- Mustache HTML validation in `course_card.mustache` (void element trailing slash).

## 1.2.28 - 2026-08-19

### Changed

- Custom headline and message editors now support embedded images and media with
  the file picker (draft-area file handling for embedded content).

## 1.2.24 - 2026-08-17

### Fixed

- HTML editor preview for completion message fields: themes that override editor
  CSS without supplying `editor.scss` now inherit Boost editor styles so Bootstrap
  classes (text-primary, text-muted) render in TinyMCE.

## 1.2.23 - 2026-08-17

### Changed

- Default headline and message editor HTML now uses Bootstrap utility classes
  (fw-bold, text-primary, text-muted) instead of plugin-specific CSS classes, so
  styling preview matches in the HTML editor and on the completion page.

## 1.2.22 - 2026-08-17

### Changed

- Custom completion headline and message are HTML editor fields pre-filled with the
  default styled title and congratulations subtitle. Empty or default content
  falls back to the standard completion page markup on display.

## 1.2.21 - 2026-08-17

### Changed

- Shared `section_*_help` and `enable_help` language strings are used on both the
  site admin settings page and the course settings form. Course settings add a
  brief intro explaining inherit/override behaviour.

## 1.2.20 - 2026-08-17

### Changed

- Custom completion message replaces the default congratulations text when set,
  instead of appearing below it.

## 1.2.19 - 2026-08-17

### Changed

- Site admin settings: added descriptions for all section default toggles; Exit
  options is listed last (after Achievements), matching the course settings form.

## 1.2.18 - 2026-08-17

### Changed

- Course settings form reorganised: section toggles act as parents for related
  fields (custom message, feedback link, suggested courses), dependent fields hide
  when a section is disabled, Exit options is last, and help icons were added for
  all course completion page fields.

## 1.2.16 - 2026-08-17

### Changed

- Achievements stat boxes and badge cards use the same neutral grey surface
  (`#f3f4f6`) as the hero meta panel, instead of blue-tinted backgrounds.

## 1.2.15 - 2026-08-17

### Changed

- Site admin settings: section defaults use their own heading instead of
  duplicating the page title.

## 1.2.14 - 2026-08-17

### Changed

- Linked Feedback activity is optional. When left on Auto, the completion page
  uses the first visible Feedback activity in the course. The dropdown remains
  as an override when a course has multiple Feedback activities.

## 1.2.13 - 2026-08-17

### Changed

- Achievements omit missing items entirely (no “not available” / “no badges”
  empty-state messages). Only real grade, time spent, badges, and completion
  date values are shown.

## 1.2.12 - 2026-08-17

### Fixed

- Feedback section no longer looks like an on-page rating form. Fake stars/comment
  fields are replaced with a clear CTA that opens the linked Feedback activity,
  where students submit their answers.

### Changed

- Achievements section surfaces completion date, course grade, time spent, and
  badges when values exist. Site badges are included alongside course badges.

## 1.2.11 - 2026-08-17

### Fixed

- Feedback section never appeared because visibility was checked via `$cm->uservisible`
  on a `get_coursemodule_from_id()` record (that property is only set on `cm_info`).
  Visibility is now resolved through `get_fast_modinfo()`.

## 1.2.10 - 2026-08-17

### Changed

- Removed the duplicate page heading (“Course completed”); the first section title
  is the only heading.

## 1.2.9 - 2026-08-17

### Fixed

- Certificate section is hidden when the certificate activity is hidden (or
  otherwise not visible to the learner). Issued certs from non-visible course
  modules are omitted.

## 1.2.8 - 2026-08-17

### Changed

- Course administration **Course Completion Page** link now opens advanced course
  settings (`/course/edit.php?…&advanced=1`).

## 1.2.6 - 2026-08-14

### Fixed

- Suggested course card enrol / add-to-cart buttons align with homepage catalog
  behaviour when an ecommerce plugin is present (handlers loaded and enrol CTA
  data merged the same way as frontpage course cards).

## 1.2.5 - 2026-08-14

### Changed

- Completion page accents now follow the theme primary colour instead of a
  hardcoded green palette.
- Certificate actions are stacked in two rows; share and “preview full
  certificate” were removed.
- Suggested courses uses a searchable course picker in course settings, and falls
  back to the same category when none are selected.

### Fixed

- Certificate section stays hidden when the learner has no issued certificate.
- Large hero check icon removed from the completion message card.

## 1.2.4 - 2026-08-14

### Fixed

- Certificate preview/download no longer hits the hidden Custom certificate
  activity page (`Sorry, this activity is currently hidden`). Issued PDFs are
  served via Custom cert’s my-certificates download URL, which still works when
  the activity is hidden on the course.

## 1.2.3 - 2026-08-10

### Changed

- Added site setting to prefer `local_timespent` for achievements time spent when
  that plugin is installed (optional; not a Moodle plugin dependency).

## 1.2.1 - 2026-08-04

### Changed

- Time spent now supports only two selectable sources (site setting):
  - `local_timespent` (default, recommended)
  - Standard logs (`logstore_standard`) session-gap estimate
- Settings page recommends installing `local_timespent` when it is not available.

## 1.2.0 - 2026-08-04

### Added

- Marketplace packaging: full GPLv3 `LICENSE`, `CHANGES.md`, `thirdpartylibs.xml`,
  and GitHub Actions CI scaffold.
- `$plugin->supported` for Moodle 4.5–5.2 and `MATURITY_STABLE` release metadata.
- Achievements section (course grade, badges, optional time spent via soft plugin
  detection).
- Certificate preview helpers for issued certificates.
- MooPlugins product documentation and README.

### Changed

- Privacy API uses `null_provider` (plugin stores course settings only; no learner
  personal data).
- Soft-guard when `mod_feedback` is missing or disabled.
- Learner-facing strings moved fully into the English language pack.

### Fixed

- Course progress percentage is not treated as course completion (gate uses Moodle
  course completion only).

## 1.1.2 - 2026-07-30

### Changed

- Certificate preview styling (removed gold ornamental frame; softened PDF viewer
  chrome).

## 1.1.1 - 2026-07-30

### Changed

- Suggested courses, feedback, exit, and footer UI aligned to product mockup.

## 1.1.0 - 2026-07-30

### Added

- Achievements section with grade, badges, and soft time-spent detection.

## 1.0.0 - 2026-07-30

### Added

- Initial Course Completion Page for Moodle.
- Site defaults and per-course settings (inherit / enable / disable).
- Completion message, certificates (`mod_customcert`), feedback CTA
  (`mod_feedback`), suggested courses, and exit actions.
