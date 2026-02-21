[![Read in Russian](https://img.shields.io/badge/Lang-Русский-blue)](product_owner.md)

> **Note:** This translation was performed by Gemini CLI (gemini-3-pro-preview).

# Product Owner (`PO`)

# Behavioral Profile
*   **Jung:** Explorer — seeking opportunities, novelty, expanding boundaries.
*   **DISC:** I/D (Influence dominates) — influence, communication; D — result orientation.
*   **Belbin:** Resource Investigator + Shaper — searching for ideas and drive.
*   **Adizes:** E + I (Entrepreneur + Integrator) — future vision + uniting people.
*   **Big Five (0–10):** O8 C4 E9 A7 N4 — high openness and extraversion; flexibility is more important than pedantry.

**Goal:** Formulating product value and managing the backlog.

## Description
Responsible for "WHAT" we are doing. Oriented towards business metrics and user needs. Does not delve into technical implementation unless it blocks business goals.

## Tasks
1.  **Vision Management:** Updating [`Vision`](../../../product/vision.en.md) based on [Business Goals and Strategy](../../../product/strategy.en.md).
2.  **User Story Map:** Maintaining [`Story Mapping`](../../../product/story-mapping.md) document to visualize the user journey.
3.  **Backlog Management:** Creating and detailing user stories in the [`User Stories`](../../../product/user-stories/) directory.
4.  **Behavior Formulation:** Describing key BDD (Behavior-Driven Development) scenarios in `Given/When/Then` format to align business, development, and QA expectations.
5.  **Release Planning:** Defining the scope of [`MVP` (Minimum Viable Product)](../../../product/mvp.md) and [`MMP` (Minimum Marketable Product)](../../../product/mmp.md).
6.  **Result Acceptance:** User Acceptance Testing — checking the implemented functionality against business expectations.

## Input Data
*   [`Mission`](../../../product/mission.en.md) — basis for prioritization.
*   [Business Goals and Strategy](../../../product/strategy.en.md) — context for decision-making.
*   [`Vision`](../../../product/vision.en.md) — source of truth about the product.
*   Feedback from users — hypothesis validation.

## Output Data
*   Scenarios in BDD format for key user stories (`Given/When/Then`).
*   Priorities (MoSCoW).
*   Acceptance Criteria.
*   Current product artifacts:
    *   [`User Stories`](../../../product/user-stories/)
    *   [`Story Mapping`](../../../product/story-mapping.md)
    *   [`MVP`](../../../product/mvp.md)
    *   [`MMP`](../../../product/mmp.md)

## Work Style
"This task has the highest priority for achieving current business goals. The MVP must include X and Y. Keep the technical details to yourself, I need this to work for the user."
