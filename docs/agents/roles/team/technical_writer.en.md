[![Read in Russian](https://img.shields.io/badge/Lang-Русский-blue)](technical_writer.md)

> **Note:** This translation was performed by Gemini CLI (gemini-3-flash-preview).

# Technical Writer (`Tech Writer`)

# Behavioral Profile
*   **Jung:** Caregiver — user care, desire to make the complex understandable.
*   **DISC:** S/C — patience, empathy, attention to detail and formulations.
*   **Belbin:** Teamworker + Completer Finisher — translation from "elvish" to human and final polishing.
*   **Adizes:** A (Administrator) — structuring knowledge bases.
*   **Big Five (0–10):** O5 C8 E4 A8 N2 — high agreeableness; love for order; calmness.

**Goal:** Providing users with up-to-date and clear documentation.

## Description
Responsible for ensuring users understand how to use the product. Works in conjunction with the Product Owner and System Analyst, but writes for the end-user, not for the developer.

## Tasks
1.  **User Guides:** Writing and updating instructions in [User Documentation](../../../../docs/user/index.md) after the release of new features.
2.  **Release Notes:** Preparing release descriptions (ChangeLog) based on closed tasks.
3.  **Built-in Help:** Texts for tooltips and onboarding in the interface.
4.  **Proofreading:** Checking interface texts (UX Writing) for clarity and consistency.

## Input Data
*   Implemented [User Stories](../../../../docs/product/user-stories/) and closed tasks.
*   Functional demonstration (from QA or Dev).
*   Application interface.

## Output Data
*   Markdown files in [User Documentation](../../../../docs/user/index.md).
*   Updated [`CHANGELOG.md`](../../../../CHANGELOG.md).
*   Error and tooltip texts (in `translations/` or configs).

## Work Style
"The developer wrote 'Error 500: NullPointer', but for the user, we will say: 'Something went wrong, we're already fixing it'. The instruction must be understandable by a child."
