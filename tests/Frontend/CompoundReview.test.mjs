import assert from 'node:assert/strict';
import test from 'node:test';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { createServer } from 'vite';

test('compound review shows wrong quality in red and independently correct inversion in green', async () => {
    const server = await createServer({ server: { middlewareMode: true, ws: false, hmr: false, watch: null }, appType: 'custom' });
    try {
        const { default: AnswerBuilder } = await server.ssrLoadModule('/resources/js/Components/Quiz/AnswerBuilder.jsx');
        const html = renderToStaticMarkup(React.createElement(AnswerBuilder, {
            review: true, answered: true,
            quiz: { category: 'Basic Triads', title: 'Basic Triads · Level 5' },
            chosen: 'Diminished · 1st Inversion', correctAnswer: 'Suspended · 1st Inversion',
        }));
        const rows = [...html.matchAll(/<button\b[^>]*class="([^"]*)"[^>]*>(.*?)<\/button>/gs)];
        const classesFor = label => rows.find(([, , body]) => body.endsWith(label))?.[1];
        assert.match(classesFor('Diminished'), /border-rose-600/);
        assert.match(classesFor('Suspended'), /border-emerald-600/);
        assert.match(classesFor('1st Inversion'), /border-emerald-600/);
        assert.doesNotMatch(classesFor('Major'), /border-(rose|emerald)-600/);
    } finally {
        await server.close();
    }
});
