# Codex CLI — Issue & PR Templates (English)

Use these templates to file issues and prepare PRs aligned with AGENTS.md and the Codex CLI workflow.

## Feature Request (Issue)

Title: feat(<module>): <short summary>

- Goal: <what outcome the feature delivers>
- Module/Layer: <ModuleName> — <Domain|Application|Infrastructure|Integration>
- Context: PHP 8.3, Symfony 7.3, related links (issues/specs)
- Acceptance Criteria:
  - <criterion 1>
  - <criterion 2>
- Scope: <in scope>
- Out of Scope: <excluded>
- Architecture: DDD boundaries respected; controllers call UseCases only; no Domain ← Infrastructure
- Data & API:
  - DB impact: <none/describe migrations>
  - Contracts: <DTOs/events/messages>
- Testing Plan:
  - Unit: <classes/value objects/use cases>
  - Integration: <adapters/handlers/repos with fakes>
  - E2E (if applicable): <web/api/console scenario>
- Risks/Trade‑offs: <notes>
- Rollback: <how to disable/rollback>

## Bug Report (Issue)

Title: fix(<module>): <symptom or failing path>

- Versions: PHP 8.3, Symfony 7.3
- Repro Steps: <exact steps or failing test>
- Actual Result: <what happens>
- Expected Result: <what should happen>
- Scope: <where to change>
- Logs/Artifacts: <stack trace, test output>
- Testing: unit test reproduces bug; integration test validates interaction

## Pull Request Template

Title: <type>(<scope>): <summary>

- Summary: <what and why>
- Affected Modules/Layers: <list>
- Changes:
  - Code: <high‑level bullets>
  - Migrations/Config: <if any>
  - Public API: <new/changed/removed>
- Tests:
  - Unit: <added/updated>
  - Integration: <added/updated>
  - Coverage: >=80% for new code
- Validation (paste outputs):
  - `make tests-unit`: <result>
  - `make psalm`: <result>
  - `make phpcs`: <result>
  - `make deptrac`: <result>
  - `make audit`: <result>
- Architecture: DDD boundaries respected; no cross‑layer leaks
- Temporary Decisions: `@todo`/`@techdebt` with date and reason
- Risks/Backward Compatibility: <notes>
- Links: <issues/docs/AGENTS.md sections>
