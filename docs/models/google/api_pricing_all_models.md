Source: https://ai.google.dev/gemini-api/docs/pricing
Last updated on page: 2025‑10‑24 UTC

## Gemini 2.5 Pro

| Tier                    | Unit   | Input price (per 1M) | Output price (per 1M) | Context caching (per 1M) | Caching storage (per 1M tokens·h) | Search grounding                    | Maps grounding                       |
|-------------------------|--------|----------------------|-----------------------|--------------------------|-----------------------------------|-------------------------------------|--------------------------------------|
| Standard (≤200k prompt) | tokens | $1.25                | $10.00                | $0.125                   | $4.50                             | 1,500 RPD free, then $35/1k prompts | 10,000 RPD free, then $25/1k prompts |
| Standard (>200k prompt) | tokens | $2.50                | $15.00                | $0.25                    | $4.50                             | 1,500 RPD free, then $35/1k prompts | 10,000 RPD free, then $25/1k prompts |
| Batch (≤200k prompt)    | tokens | $0.625               | $5.00                 | $0.125                   | $4.50                             | 1,500 RPD free, then $35/1k prompts | Not available                        |
| Batch (>200k prompt)    | tokens | $1.25                | $7.50                 | $0.25                    | $4.50                             | 1,500 RPD free, then $35/1k prompts | Not available                        |

## Gemini 2.5 Flash (and Flash Preview)

| Model             | Tier     | Unit          | Input price                             | Output price           | Context caching (per 1M)               | Caching storage (per 1M tokens·h) | Search grounding                           | Maps grounding                        | Live API                                                                              |
|-------------------|----------|---------------|-----------------------------------------|------------------------|----------------------------------------|-----------------------------------|--------------------------------------------|---------------------------------------|---------------------------------------------------------------------------------------|
| 2.5 Flash         | Standard | per 1M tokens | $0.30 (text/image/video); $1.00 (audio) | $2.50 (incl. thinking) | $0.03 (text/image/video); $0.1 (audio) | $1.00                             | 500 RPD free → 1,500 RPD free, then $35/1k | 500 RPD → 1,500 RPD free, then $25/1k | Input: $0.50 (text), $3.00 (audio/image[video]); Output: $2.00 (text), $12.00 (audio) |
| 2.5 Flash         | Batch    | per 1M tokens | $0.15 (text/image/video); $0.50 (audio) | $1.25                  | $0.03 (text/image/video); $0.1 (audio) | $1.00                             | 1,500 RPD free, then $35/1k                | Not available                         | N/A                                                                                   |
| 2.5 Flash Preview | Standard | per 1M tokens | $0.30 (text/image/video); $1.00 (audio) | $2.50 (incl. thinking) | $0.03 (text/image/video); $0.1 (audio) | $1.00                             | 500 RPD free → 1,500 RPD free, then $35/1k | —                                     | Input: $0.50 (text), $3.00 (audio/image[video]); Output: $2.00 (text), $12.00 (audio) |
| 2.5 Flash Preview | Batch    | per 1M tokens | $0.15 (text/image/video); $0.50 (audio) | $1.25                  | $0.03 (text/image/video); $0.1 (audio) | $1.00                             | 1,500 RPD free, then $35/1k                | Not available                         | N/A                                                                                   |

## Gemini 2.5 Flash‑Lite (stable & preview)

| Model                  | Tier     | Unit          | Input price                             | Output price | Context caching (per 1M)                | Storage | Search grounding                           | Maps grounding                        |
|------------------------|----------|---------------|-----------------------------------------|--------------|-----------------------------------------|---------|--------------------------------------------|---------------------------------------|
| 2.5 Flash‑Lite         | Standard | per 1M tokens | $0.10 (text/image/video); $0.30 (audio) | $0.40        | $0.01 (text/image/video); $0.03 (audio) | $1.00   | 500 RPD free → 1,500 RPD free, then $35/1k | 500 RPD → 1,500 RPD free, then $25/1k |
| 2.5 Flash‑Lite         | Batch    | per 1M tokens | $0.05 (text/image/video); $0.15 (audio) | $0.20        | $0.01 (text/image/video); $0.03 (audio) | $1.00   | 1,500 RPD free, then $35/1k                | Not available                         |
| 2.5 Flash‑Lite Preview | Standard | per 1M tokens | $0.10 (text/image/video); $0.30 (audio) | $0.40        | $0.01 (text/image/video); $0.03 (audio) | $1.00   | 500 RPD free → 1,500 RPD free, then $35/1k | —                                     |
| 2.5 Flash‑Lite Preview | Batch    | per 1M tokens | $0.05 (text/image/video); $0.15 (audio) | $0.20        | $0.01 (text/image/video); $0.03 (audio) | $1.00   | 1,500 RPD free, then $35/1k                | —                                     |

## Audio models

| Model                            | Tier     | Unit          | Input price                       | Output price                 |
|----------------------------------|----------|---------------|-----------------------------------|------------------------------|
| 2.5 Flash Native Audio (Preview) | Standard | per 1M tokens | $0.50 (text); $3.00 (audio/video) | $2.00 (text); $12.00 (audio) |
| 2.5 Flash Preview TTS            | Standard | per 1M tokens | $0.50 (text)                      | $10.00 (audio)               |
| 2.5 Flash Preview TTS            | Batch    | per 1M tokens | $0.25 (text)                      | $5.00 (audio)                |
| 2.5 Pro Preview TTS              | Standard | per 1M tokens | $1.00 (text)                      | $20.00 (audio)               |
| 2.5 Pro Preview TTS              | Batch    | per 1M tokens | $0.50 (text)                      | $10.00 (audio)               |

## Image generation (Imagen / 2.5 Flash Image)

| Model             | Tier     | Unit      | Price    |
|-------------------|----------|-----------|----------|
| Imagen 4 Fast     | Standard | per image | $0.02    |
| Imagen 4 Standard | Standard | per image | $0.04    |
| Imagen 4 Ultra    | Standard | per image | $0.06    |
| Imagen 3          | Standard | per image | $0.03    |
| 2.5 Flash Image   | Standard | per image | $0.039*  |
| 2.5 Flash Image   | Batch    | per image | $0.0195* |

*Footnote: Image output is priced at $30 per 1,000,000 tokens. Images up to 1024×1024 consume 1,290 tokens → ~$0.039 each.*

## Video generation (Veo)

| Model                               | Unit       | Price |
|-------------------------------------|------------|-------|
| Veo 3.1 Standard (video with audio) | per second | $0.40 |
| Veo 3.1 Fast (video with audio)     | per second | $0.15 |
| Veo 3 Standard (video with audio)   | per second | $0.40 |
| Veo 3 Fast (video with audio)       | per second | $0.15 |
| Veo 2 (video)                       | per second | $0.35 |

## Gemini 2.0 family

| Model          | Tier     | Unit          | Input price                             | Output price | Context caching (per 1M)                  | Storage | Image generation   | Search grounding                           | Maps grounding                        | Live API                                                                             |
|----------------|----------|---------------|-----------------------------------------|--------------|-------------------------------------------|---------|--------------------|--------------------------------------------|---------------------------------------|--------------------------------------------------------------------------------------|
| 2.0 Flash      | Standard | per 1M tokens | $0.10 (text/image/video); $0.70 (audio) | $0.40        | $0.025 (text/image/video); $0.175 (audio) | $1.00   | $0.039 per image*  | 500 RPD free → 1,500 RPD free, then $35/1k | 500 RPD → 1,500 RPD free, then $25/1k | Input: $0.35 (text), $2.10 (audio/image[video]); Output: $1.50 (text), $8.50 (audio) |
| 2.0 Flash      | Batch    | per 1M tokens | $0.05 (text/image/video); $0.35 (audio) | $0.20        | $0.025 (text/image/video); $0.175 (audio) | $1.00   | $0.0195 per image* | 1,500 RPD free, then $35/1k                | Not available                         | N/A                                                                                  |
| 2.0 Flash‑Lite | Standard | per 1M tokens | $0.075                                  | $0.30        | —                                         | —       | —                  | —                                          | —                                     | —                                                                                    |
| 2.0 Flash‑Lite | Batch    | per 1M tokens | $0.0375                                 | $0.15        | —                                         | —       | —                  | —                                          | —                                     | —                                                                                    |

*Footnote: Image output via 2.0 Flash is priced at the same $30 / 1M tokens (≈$0.039 per 1024×1024 image); batch ≈$0.0195.*

## Embeddings

| Model                | Tier     | Unit          | Input price |
|----------------------|----------|---------------|-------------|
| gemini-embedding-001 | Standard | per 1M tokens | $0.15       |
| gemini-embedding-001 | Batch    | per 1M tokens | $0.075      |

## Specialized models

| Model                           | Tier             | Unit          | Input price                             | Output price | Search grounding                           |
|---------------------------------|------------------|---------------|-----------------------------------------|--------------|--------------------------------------------|
| Gemini Robotics‑ER 1.5 Preview  | Standard         | per 1M tokens | $0.30 (text/image/video); $1.00 (audio) | $2.50        | 500 RPD free → 1,500 RPD free, then $35/1k |
| Gemini 2.5 Computer Use Preview | Standard (≤200k) | per 1M tokens | $1.25                                   | $10.00       | —                                          |
| Gemini 2.5 Computer Use Preview | Standard (>200k) | per 1M tokens | $2.50                                   | $15.00       | —                                          |

## Gemma (open models)

| Model    | Tier | Input / Output price                                |
|----------|------|-----------------------------------------------------|
| Gemma 3  | Free | Input: Free, Output: Free (paid tier not available) |
| Gemma 3n | Free | Input: Free, Output: Free (paid tier not available) |
