# Directory Core Roadmap

This document is the living roadmap and requirements record for the shared directory ecosystem. It should stay implementation-facing enough for future development work while remaining readable for product and client planning.

## Product Vision

The directory should help a community arts organization maintain a trusted public roster of artists, venues, and related records. The immediate need is a staff-managed public artist directory. The longer-term goal is a workflow where artists can submit themselves for inclusion, staff can approve and curate those records, and approved artists can eventually help maintain their own information without publishing unreviewed changes.

The directory should serve three audiences:

- Community visitors who want to find artists, understand their media or specialties, and get in touch with a relevant artist.
- Staff who need to review submissions, approve records, curate public visibility, and respond to community requests.
- Artists who want to submit themselves for inclusion and eventually manage their own directory details.

Example visitor use case: a community member is looking for mural artists. Staff should be able to direct them to the directory, where they can filter or browse to find an appropriate artist and contact them through the approved public workflow.

## Current Foundation

Directory Core currently owns the shared record model and administrative foundation:

- `mw_artist` canonical artist records.
- `mw_venue` canonical venue records.
- `mw_media` taxonomy for artist filtering and discovery.
- Visibility states for internal records, public directory listings, and public profile pages.
- Related artist/venue relationships.
- Profile artwork gallery metadata.
- Public contact/link metadata, including contact preference, inquiry email, website, social URL, primary contact, and inquiry availability.
- Internal referral metadata, including vetted/recommended state, last reviewed date, submission source, referral notes, staff notes, and do-not-refer notes.
- `mw_owner_user_id` as an early placeholder for account ownership.
- Additional artist discovery taxonomies:
  - `mw_artist_service` for services and opportunities such as murals, commissions, teaching, workshops, and public art.
  - `mw_artist_audience` for audiences and settings such as schools, families, corporate, festivals, and public spaces.
  - `mw_artist_project_scale` for small commissions, large murals, public installations, temporary works, and permanent works.
  - `mw_artist_availability` for accepting commissions, teaching availability, public art availability, and related status terms.
  - `mw_artist_service_area` for location and travel/service coverage.

The Artist Directory plugin currently owns the public-facing directory experience:

- Public artist archive and block rendering.
- Card and list views.
- Media filtering.
- Public artist profile pages.
- Configurable directory page setting.
- Light/dark display modes.

Future work should keep shared data, ownership, approval, and governance behavior in Directory Core. Public display behavior should remain in Artist Directory unless it becomes shared across multiple directory products.

## Roadmap Phases

### Phase 1: Staff-Managed MVP

Status: In progress.

The first milestone is a staff-managed public directory. Staff create and maintain artist records directly in WordPress, choose visibility state, add media terms, assign related venues, and manage profile artwork.

Key capabilities:

- Public directory page with card and list views.
- Media filters for visitor browsing.
- Profile-only artist singles.
- Directory-only listings that do not expose public profile pages.
- Internal records omitted from the public directory.
- Configurable directory page used by public links.
- Staff-editable contact, referral, and discovery fields on artist records.
- Staff-editable discovery taxonomies for services, audiences/settings, project scale, availability, and service area.

### Phase 2: Gravity Forms Intake

Status: Planned.

Use Gravity Forms as the first submission layer because staff are comfortable managing forms there. The first implementation should be configuration-first: use Gravity Forms feeds and standard WordPress/Gravity Forms behavior wherever possible before adding plugin integration code.

Directory Core includes a Gravity Forms Intake helper under Creative Directory that provides a starter artist intake form export, checks whether Gravity Forms and Advanced Post Creation are active, and documents the recommended feed mapping.

Target capabilities:

- Public artist self-submission form.
- Submitted entries create either pending/internal artist records or intake entries for staff review.
- Submission fields map to artist identity, media terms, biography/content, images, contact details, and optional venue relationships.
- Staff can review submissions before anything becomes publicly visible.

Plugin code should only be added when Gravity Forms configuration is not enough for reliable mapping, validation, approval workflow, or staff usability.

### Deployment Readiness: Artist Intake Launch

Status: Next.

Before giving the client a live artist intake link, complete a small launch-readiness pass so submissions are reviewable, private by default, and staff knows where to look.

Recommended order:

- Install and activate Gravity Forms Advanced Post Creation on the client or staging site.
- Import the starter artist intake form from Creative Directory > Settings > Gravity Forms Intake.
- Configure the Advanced Post Creation feed to create `mw_artist` records with `post_status` set to `pending`.
- Map the feed so submitted artist name becomes the post title, biography becomes post content, contact fields map to Directory Core meta, and supported taxonomy fields map to media/discovery taxonomies.
- Default new submissions to `mw_visibility_state = internal` and `mw_artist_submission_source = gravity_forms`.
- Submit two or three test entries and confirm staff can review the pending artist records before anything becomes public.
- Confirm the staff review workflow: pending artist -> staff review/edit -> visibility changed to `directory` or `profile` only when approved.
- Add clear intake form language covering no guarantee of listing, staff review/curation, and which contact information may become public.
- Have the client review the taxonomy choices for media, services, audiences/settings, project scale, availability, and service area before public submissions begin.
- Soft launch the form on an unlinked/private page first, then verify public directory listing, profile display, contact fields, images, and filters after approval.
- Add a staff dashboard widget soon after launch to surface pending artist submissions and recent intake activity.
- Commit and tag the deploy state before packaging or installing on the client site.

### Phase 3: Staff Approval Dashboard

Status: Planned.

Add a WordPress dashboard widget or admin landing view that helps staff see directory work needing attention.

Target capabilities:

- Pending artist submissions.
- Recently submitted artist updates.
- Quick links to review, approve, reject, or edit records.
- Clear distinction between new submissions and proposed changes to existing records.

### Phase 4: Artist Accounts and Ownership

Status: Planned.

Create a limited artist user role that can access only the directory management surfaces intended for artist users. Replace the single-owner placeholder with a multi-owner model.

Target capabilities:

- New limited artist role.
- One or more users assigned as owners of an artist or venue record.
- Staff-controlled owner assignment.
- Artist users can only view or submit changes for records they own.
- Ownership model works for both artists and venues when there is a real association.

The existing `mw_owner_user_id` field should be treated as a temporary placeholder. Future implementation should support multiple owners per record.

### Phase 5: Staged Artist-Managed Updates

Status: Wishlist.

Artist users should be able to propose updates to records they own, but changes should require staff approval before becoming public.

Target capabilities:

- Artist-facing edit request workflow.
- Proposed changes stored separately from the live record.
- Staff review screen showing what changed.
- Staff can approve, reject, or request follow-up.
- Approved changes update the live artist or venue record.

The preferred model is staged changes rather than direct edits to public records.

### Phase 6: Contact and Discovery Improvements

Status: Wishlist.

Improve the directory as a discovery and referral tool for community requests.

Potential capabilities:

- Public contact workflow for artists.
- Staff-mediated inquiry workflow when direct public contact is not appropriate.
- Richer filters for use cases such as mural artists, teaching artists, commissions, availability, or location.
- Better public profile fields for specialties, website/social links, and contact preferences.
- Internal staff notes that help with referrals but are never shown publicly.

Implementation note: the data fields and taxonomies for these richer filters can exist before they are exposed on the public directory. Public filtering and profile display should be added intentionally after staff confirms which terms and contact fields are safe to expose.

## Architecture Recommendations

- Directory Core should own shared records, ownership, approval state, artist/venue account relationships, roles, and submission-review concepts.
- Artist Directory should own public archive, profile, filter, block, and presentation behavior.
- Gravity Forms should be the first intake mechanism, configured first and extended with code only where needed.
- Public-facing links to the directory should use the configured directory page when one exists.
- Artist-managed edits should never publish directly without staff approval.

## Open Questions

- What exact Gravity Forms fields should map to artist post title, display name, biography, media terms, contact fields, images, and venue relationships?
- Should submitted forms create draft/internal `mw_artist` posts immediately, or should they remain Gravity Forms entries until staff accepts them?
- Should public contact be direct artist contact, a form relay, or staff-mediated?
- Which contact details are public, private to staff, or hidden entirely?
- Should staged updates support field-by-field review, or should staff approve/reject the whole proposed change set?
- What notifications should staff receive for new submissions and proposed updates?
- What notifications should artists receive when submissions or updates are approved/rejected?
- Should artists be able to upload and reorder gallery images, or only request image changes?
- Should venues eventually follow the same submission and ownership workflow as artists?

## Decision Log

- The living roadmap lives in Directory Core because submissions, ownership, approval, roles, and shared records are cross-product concerns.
- Gravity Forms is the preferred initial intake layer, using a configuration-first approach.
- Initial intake launch should use Gravity Forms Advanced Post Creation to create pending, internal artist records for staff review.
- The target ownership model is multiple owner users per artist or venue record.
- Artist-managed edits should use staged changes that require staff approval before affecting live public records.
- Public presentation remains the responsibility of the Artist Directory plugin unless the behavior becomes shared by future directory products.
