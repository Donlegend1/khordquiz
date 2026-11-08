// Shuffle the full pool without mutating its original order.
export function shuffled(ids) {
    const result = [...ids];
    for (let i = result.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [result[i], result[j]] = [result[j], result[i]];
    }
    return result;
}

// A fresh reshuffle can otherwise land the clip that JUST finished back in
// slot 0 of the new pass — an effective repeat the "no repeat" rule should
// also cover, even though it's not within the same pass.
export function reshuffleAvoiding(ids, avoidId) {
    const next = shuffled(ids);
    if (next.length > 1 && next[0] === avoidId) {
        const swapAt = 1 + Math.floor(Math.random() * (next.length - 1));
        [next[0], next[swapAt]] = [next[swapAt], next[0]];
    }
    return next;
}

