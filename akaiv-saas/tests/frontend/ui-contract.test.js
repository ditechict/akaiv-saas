import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

const resourcePath = new URL('../../app/Filament/Resources/DocumentResource.php', import.meta.url)
const themePath = new URL('../../resources/css/filament/admin/theme.css', import.meta.url)
const providerPath = new URL('../../app/Providers/Filament/AdminPanelProvider.php', import.meta.url)

test('document resource exposes accessible field labels and destructive-action consequences', async () => {
    const resource = await readFile(resourcePath, 'utf8')

    assert.match(resource, /TextInput::make\('friendly_name'\)->label\('Document name'\)/)
    assert.match(resource, /TextInput::make\('original_filename'\)->label\('Original filename'\)/)
    assert.match(resource, /DeleteAction::make\(\)/)
    assert.match(resource, /->label\('Move to trash'\)/)
    assert.match(resource, /->modalDescription\(/)
})

test('admin theme preserves keyboard focus and mobile target sizing', async () => {
    const theme = await readFile(themePath, 'utf8')

    assert.match(theme, /:focus-visible\s*\{[\s\S]*outline: 2px solid/)
    assert.match(theme, /\.fi-btn,[\s\S]*\.fi-icon-btn\s*\{[\s\S]*min-height: 2\.75rem/)
    assert.match(theme, /prefers-reduced-motion: reduce/)
})

test('admin panel has stable AKAIV brand configuration', async () => {
    const provider = await readFile(providerPath, 'utf8')

    assert.match(provider, /->brandName\('AKAIV Archives'\)/)
    assert.match(provider, /->brandLogo\(asset\('images\/akaiv-logo\.svg'\)\)/)
    assert.match(provider, /->favicon\(asset\('images\/akaiv-mark\.svg'\)\)/)
    assert.match(provider, /->viteTheme\('resources\/css\/filament\/admin\/theme\.css'\)/)
})