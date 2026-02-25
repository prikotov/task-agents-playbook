[![Read in Russian](https://img.shields.io/badge/Lang-Русский-blue)](vision.md)

> **Note:** This translation was performed by Gemini CLI (gemini-3-flash-preview).

# Product Vision: TasK

## 1. Vision Statement (Template 2.1)

**For** development and business teams **who have a problem with** the complexity and labor-intensiveness of extracting structured knowledge from large volumes of unstructured data (complex PDFs, audio recordings, web sources), **our product TasK** **will help by** becoming a reliable and scalable pipeline for automatic processing, analysis, and indexing of information, **and will be a better fit than competitor offerings** (such as scattered scripts or closed SaaS solutions) **because** it combines the power of modern AI tools with industrial reliability of enterprise class, ensuring full control over data and the processing flow.

---

## 2. Elevator Pitch

"Imagine that thousands of your documents, reports, and meeting records have turned into a living knowledge base that answers questions itself. **TasK** is an intelligent pipeline that takes on all the 'dirty' work: it downloads data, recognizes complex PDFs, transcribes audio and video with **speaker identification**, and prepares information for AI. Unlike closed SaaS solutions, TasK is deployed on your infrastructure — full control over data, no monthly subscriptions, and independence from external providers."

---

## 3. Design the Box

### Front
*   **Title:** TasK: Knowledge Engineering Platform
*   **Slogan:** From document chaos to data intelligence.
*   **Visual Image:** A stylized conveyor belt entering with stacks of papers, audio recordings, and video clips, exiting with a glowing crystal (structured data) connected to a neural network.

### Back
**TasK** — is your personal factory for processing data into knowledge.

**Key Features:**
*   **Smart Ingestion:** Automatic data collection from cloud storage, web resources, and corporate systems.
*   **Deep Processing:** High-precision extraction of meaning from complex documents and media content.
*   **Speaker Diarization:** Speaker identification in audio/video — extracting expert opinions attributed to the author.
*   **Semantic Search:** Instant search "by meaning" rather than keywords across the entire company knowledge base.
*   **Reliable Engineering:** Industrial architecture guaranteeing data integrity and scalability for any task.

**Result:** Your company receives a foundation for implementing smart assistants on its own data without the risk of information leakage.

---

## 4. Goals for the Near Future (What we are doing)

> **Baseline:** 2026-02-25

*   **In six months (2026-08-25)**, we will provide users with the ability to connect any S3 archive and receive a fully indexed knowledge base in one click.
*   **In one year (2027-02-25)**, our product will allow for the automation of 90% of routine incoming documentation analysis, ensuring data extraction accuracy at the level of a human expert.

---

## 5. Problem Statement

The problem TasK solves:

1. **Information Overload**: Companies accumulate vast amounts of unstructured data (PDFs, audio recordings, videos, notes) but cannot use them effectively.
2. **Search Difficulties**: Searching document content is often limited to keywords and does not account for meaning.
3. **Manual Labor**: Extracting knowledge from documents requires significant human effort.
4. **Fragmented Solutions**: Using separate Python scripts or closed SaaS services does not provide a holistic picture of data control.

---

## 6. Target Users

### Primary Users
- **Internal Team**: developers and engineers of the TasK project — first users for testing and feedback.
- **Developers**: technical specialists who integrate TasK into their systems via API.
- **Knowledge Workers**: employees working with large volumes of documentation requiring fast search.

### Secondary Users
- **Product Managers**: create and configure projects for teams.
- **Data Analysts**: use RAG capabilities for analytics.

---

## 7. Value Proposition

For users, TasK provides:

1. **Time**: automation of routine data processing frees up time for high-level tasks.
2. **Quality**: AI-first approach ensures more accurate meaning extraction than keywords.
3. **Sovereignty**: self-hosted and local processing ensure privacy and control over data, as well as independence from vendor lock-in.
4. **Integration**: open API and open standards allow embedding TasK into existing workflows.

---

## 8. Product Principles

1. **AI-First**: AI is not an add-on but a fundamental part of the system.
2. **Privacy-First**: user data remains under their control; self-hosted/on-premise deployment and independence from vendor lock-in.
3. **Developer-Friendly**: clean API, documentation, self-hosting capability, and integration via open standards.
4. **Markdown-First**: preference for markdown format for storing and working with content.
5. **Extensibility**: plugins for different data sources and formats.

---

## 9. Non-Goals

What TasK definitely **DOES NOT** do:

- Does not compete with Notion/Obsidian as a document editor — we focus on processing and RAG.
- Does not provide social features — comments, reviews, collaborative editing.
- Does not develop mobile applications — focus on web/API/CLI.
- Does not host user data as SaaS — priority: self-hosted deployment.
- Is not a universal CRM/ERP system — we specialize in knowledge engineering.

---

## 10. Success Metrics

### Key Metrics
- **Documents processed**: number of successfully processed documents.
- **Search success rate**: share of successful search queries (relevant results).
- **API usage**: number of API requests (mean, p95, p99).
- **Indexing time**: average time for indexing a document.

### Metabolic Metrics
- **Retention rate**: share of users who continue using TasK.
- **Query latency**: response latency for a search query.

---

## 11. Scope Boundaries

### What's IN TasK

- Source Ingestion (S3, HTTP, local files)
- Text Extraction (PDF, DOCX, TXT)
- Audio/Video Transcription
- **Audio Diarization (speaker identification)**
- Chunking and embeddings for RAG
- Vector search (semantic search)
- API for working with the index
- CLI for ingestion

### What's NOT in TasK

- OCR recognition of scanned images (handwriting)
- Full NLP analysis (sentiment, NER, etc.)
- Document-level access management
- Data visualization/dashboards
- Report generation and analytics
- Integration with Jira/Trello/etc.
- Mobile apps
- SaaS data hosting

---

## 12. Glossary

| Term | Description |
|--------|----------|
| **RAG** | Retrieval-Augmented Generation — an approach where LLM uses external data to generate answers. |
| **Chunking** | Splitting text into fragments (chunks) for subsequent vectorization. |
| **Vector DB** | Database for storing and searching vectors (embeddings). |
| **Embeddings** | Vector representations of text reflecting meaning. |
| **Source** | Data source for ingestion (S3 bucket, URL, local file). |
| **Index** | Indexed data representation for search. |
| **MVP** | Minimum Viable Product — minimum version of the product for internal use. |
| **MMP** | Minimum Marketable Product — minimum version for market entry. |
| **Ingest** | Process of loading and primary processing of data from a source. |
| **Query** | Search query to the system. |
| **Agent** | Autonomous component performing tasks (e.g., document processing). |
| **Diarization** | Speaker identification in audio/video — separating speech stream by speakers with attribution of fragments to specific persons. |
