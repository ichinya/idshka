import { mkdtemp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import assert from 'node:assert/strict';

import { compileOpenSpecRules } from '../../scripts/openspec-rules-compiler.mjs';

test('compileOpenSpecRules falls back to spec files without warning when CLI JSON has no requirement titles', async () => {
  const rootDir = await mkdtemp(path.join(os.tmpdir(), 'openspec-rules-'));

  try {
    await writeSpec(
      rootDir,
      'openspec/specs/example/spec.md',
      `# example Specification

## Requirements
### Requirement: Human Readable Base Requirement
The base capability SHALL keep the authored requirement heading.

#### Scenario: Base scenario
- **WHEN** base rules are compiled
- **THEN** the authored base requirement title is preserved.
`
    );
    await writeSpec(
      rootDir,
      'openspec/changes/change-one/specs/example-change/spec.md',
      `# Delta for Example Change

## ADDED Requirements
### Requirement: Human Readable Change Requirement
The change capability SHALL keep the authored requirement heading.

#### Scenario: Change scenario
- **WHEN** change rules are compiled
- **THEN** the authored change requirement title is preserved.
`
    );

    const result = await compileOpenSpecRules('change-one', {
      rootDir,
      detectOpenSpec: async () => ({
        available: true,
        canValidate: true,
        errors: []
      }),
      showOpenSpecItem: async (itemName) => ({
        ok: true,
        json: {
          id: itemName,
          requirementCount: 1,
          requirements: [
            {
              text: 'The CLI JSON contains requirement text but no authored heading.',
              scenarios: [
                {
                  rawText: '- **WHEN** CLI JSON is inspected\n- **THEN** it has no title field.'
                }
              ]
            }
          ]
        },
        error: null
      })
    });

    assert.equal(result.ok, true);
    assert.deepEqual(result.warnings, []);

    const merged = await readFile(
      path.join(rootDir, '.ai-factory/rules/generated/openspec-merged-change-one.md'),
      'utf8'
    );
    assert.match(merged, /### Requirement: Human Readable Base Requirement/);
    assert.match(merged, /### Requirement: Human Readable Change Requirement/);
  } finally {
    await rm(rootDir, { recursive: true, force: true });
  }
});

async function writeSpec(rootDir, relativePath, content) {
  const targetPath = path.join(rootDir, relativePath);
  await mkdir(path.dirname(targetPath), { recursive: true });
  await writeFile(targetPath, content, 'utf8');
}
