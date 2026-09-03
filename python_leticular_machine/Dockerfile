FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1

RUN apt-get update \
    && apt-get install --yes --no-install-recommends ffmpeg \
    && rm -rf /var/lib/apt/lists/* \
    && useradd --create-home --uid 10001 worker

WORKDIR /app
COPY pyproject.toml ./
COPY src ./src
RUN pip install .

RUN mkdir /app/runtime && chown worker:worker /app/runtime
USER worker

CMD ["lenticular-worker"]

